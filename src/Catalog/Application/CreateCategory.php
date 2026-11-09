<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\Catalog\Domain\CatalogRuleViolation;
use App\Catalog\Domain\Category;
use App\Catalog\Domain\CategoryRepository;
use App\Shared\Domain\TranslatedSlugs;

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
        $taken = TranslatedSlugs::taken($data->slug, $data->translations, $this->categories->findBySlug(...), null);
        if (null !== $taken) {
            throw new CatalogRuleViolation('Le slug « %slug% » est déjà pris par une autre catégorie.', ['%slug%' => $taken]);
        }
        $parent = null === $data->parentId ? null : $this->categories->find($data->parentId)
            ?? throw new CatalogRuleViolation('La catégorie parente n\'existe plus.');

        $category = new Category($data->slug, $data->name, $data->position, $parent);
        $category->changeTranslations($data->translations);
        $this->categories->save($category);

        return $category;
    }
}
