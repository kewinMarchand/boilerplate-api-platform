<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\Catalog\Domain\Category;

/**
 * Saisie d'une catégorie, remplie par l'admin. La forme (slug, longueurs) est validée à la frontière.
 */
final class CategoryData
{
    public string $slug = '';
    public string $name = '';
    public int $position = 0;
    public ?int $parentId = null;
    /** @var array<string, array<string, string|null>> traductions (ADR 0015), par langue puis par champ ; vide sur un site monolingue */
    public array $translations = [];

    public static function fromCategory(Category $category): self
    {
        $data = new self();
        $data->slug = $category->getSlug();
        $data->name = $category->getName();
        $data->position = $category->getPosition();
        $data->parentId = $category->getParent()?->getId();
        $data->translations = $category->getTranslations();

        return $data;
    }
}
