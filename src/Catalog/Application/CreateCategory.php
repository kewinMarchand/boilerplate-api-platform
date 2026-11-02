<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\Catalog\Domain\CatalogRuleViolation;
use App\Catalog\Domain\Category;
use App\Catalog\Domain\CategoryRepository;

final readonly class CreateCategory
{
    public function __construct(private CategoryRepository $categories)
    {
    }

    /**
     * @throws CatalogRuleViolation slug déjà pris, parent introuvable, 4ᵉ niveau
     */
    public function __invoke(CategoryData $data): Category
    {
        if (null !== $this->categories->findBySlug($data->slug)) {
            throw new CatalogRuleViolation(\sprintf('Le slug « %s » est déjà pris par une autre catégorie.', $data->slug));
        }
        $parent = null === $data->parentId ? null : $this->categories->find($data->parentId)
            ?? throw new CatalogRuleViolation('La catégorie parente n\'existe plus.');

        $category = new Category($data->slug, $data->name, $data->position, $parent);
        $this->categories->save($category);

        return $category;
    }
}
