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

    public static function fromCategory(Category $category): self
    {
        $data = new self();
        $data->slug = $category->getSlug();
        $data->name = $category->getName();
        $data->position = $category->getPosition();
        $data->parentId = $category->getParent()?->getId();

        return $data;
    }
}
