<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

interface CategoryRepository
{
    /**
     * @return list<Category> toutes les catégories, triées par position
     */
    public function findAll(): array;

    public function find(int $id): ?Category;

    /**
     * @param string|null $locale langue du slug (le sien, ou celui de la langue par défaut s'il n'est pas traduit) ;
     *                            null : la langue par défaut
     */
    public function findBySlug(string $slug, ?string $locale = null): ?Category;

    public function save(Category $category): void;

    public function remove(Category $category): void;
}
