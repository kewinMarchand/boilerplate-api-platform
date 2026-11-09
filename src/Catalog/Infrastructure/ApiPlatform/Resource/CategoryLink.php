<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use App\Catalog\Domain\Category;

/**
 * Étape du chemin d'un produit dans l'arbre des catégories : de quoi poser un lien de fil d'Ariane.
 */
final readonly class CategoryLink
{
    public function __construct(
        #[ApiProperty(required: true)]
        public string $slug,
        #[ApiProperty(required: true)]
        public string $name,
    ) {
    }

    /**
     * @return list<self> de la racine à la catégorie du produit
     */
    public static function pathTo(Category $category, string $locale): array
    {
        $path = [];
        for ($current = $category; null !== $current; $current = $current->getParent()) {
            array_unshift($path, new self($current->getSlug($locale), $current->getName($locale)));
        }

        return $path;
    }
}
