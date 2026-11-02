<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\Catalog\Domain\CatalogRuleViolation;
use App\Catalog\Domain\CategoryRepository;
use App\Catalog\Domain\CategoryTree;

final readonly class UpdateCategory
{
    public function __construct(private CategoryRepository $categories)
    {
    }

    /**
     * @throws CatalogRuleViolation catégorie ou parent introuvable, slug déjà pris, cycle, 4ᵉ niveau
     */
    public function __invoke(int $id, CategoryData $data): void
    {
        $category = $this->categories->find($id) ?? throw new CatalogRuleViolation('Cette catégorie n\'existe plus.');
        $sameSlug = $this->categories->findBySlug($data->slug);
        if (null !== $sameSlug && $sameSlug !== $category) {
            throw new CatalogRuleViolation(\sprintf('Le slug « %s » est déjà pris par une autre catégorie.', $data->slug));
        }
        $parent = null === $data->parentId ? null : $this->categories->find($data->parentId)
            ?? throw new CatalogRuleViolation('La catégorie parente n\'existe plus.');
        $levelsBelow = new CategoryTree($this->categories->findAll())->levelsBelow($category);

        $category->update($data->slug, $data->name, $data->position, $parent, $levelsBelow);
        $this->categories->save($category);
    }
}
