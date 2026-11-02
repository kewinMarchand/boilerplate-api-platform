<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

/**
 * L'arbre des catégories du catalogue : recherche par slug, enfants directs, branches.
 * La racine de l'arbre est représentée par null.
 */
final readonly class CategoryTree
{
    /**
     * @param list<Category> $categories
     */
    public function __construct(private array $categories)
    {
    }

    public function find(string $slug): Category
    {
        foreach ($this->categories as $category) {
            if ($category->getSlug() === $slug) {
                return $category;
            }
        }

        throw new CategoryNotFound($slug);
    }

    /**
     * @return list<Category> triées par position
     */
    public function childrenOf(?Category $parent): array
    {
        $children = array_values(array_filter($this->categories, static fn (Category $category): bool => $category->getParent() === $parent));
        usort($children, static fn (Category $a, Category $b): int => $a->getPosition() <=> $b->getPosition());

        return $children;
    }

    /**
     * @return array<string, true> slugs de la catégorie et de toutes ses descendantes (tout l'arbre si null)
     */
    public function branchSlugs(?Category $root): array
    {
        $slugs = [];
        foreach ($this->categories as $category) {
            for ($ancestor = $category; null !== $ancestor; $ancestor = $ancestor->getParent()) {
                if (null === $root || $ancestor === $root) {
                    $slugs[$category->getSlug()] = true;
                    break;
                }
            }
        }

        return $slugs;
    }

    /**
     * Nombre de niveaux sous la catégorie (0 si elle n'a pas de sous-catégorie).
     */
    public function levelsBelow(Category $category): int
    {
        $levels = 0;
        foreach ($this->childrenOf($category) as $child) {
            $levels = max($levels, $this->levelsBelow($child) + 1);
        }

        return $levels;
    }
}
