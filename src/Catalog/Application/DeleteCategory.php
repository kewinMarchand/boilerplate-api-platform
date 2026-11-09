<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\Catalog\Domain\CatalogRuleViolation;
use App\Catalog\Domain\CategoryRepository;
use App\Catalog\Domain\CategoryTree;
use App\Catalog\Domain\ProductRepository;

final readonly class DeleteCategory
{
    public function __construct(
        private CategoryRepository $categories,
        private ProductRepository $products,
    ) {
    }

    /**
     * @throws CatalogRuleViolation catégorie introuvable, ou qui contient encore des sous-catégories ou des produits
     */
    public function __invoke(int $id): void
    {
        $category = $this->categories->find($id) ?? throw new CatalogRuleViolation('Cette catégorie n\'existe plus.');
        if ([] !== new CategoryTree($this->categories->findAll())->childrenOf($category)) {
            throw new CatalogRuleViolation('« %name% » contient des sous-catégories : les déplacer ou les supprimer d\'abord.', ['%name%' => $category->getName()]);
        }
        $count = $this->products->countInCategory($category);
        if ($count > 0) {
            throw new CatalogRuleViolation('« %name% » contient %count% produit(s) : les déplacer ou les supprimer d\'abord.', ['%name%' => $category->getName(), '%count%' => $count]);
        }

        $this->categories->remove($category);
    }
}
