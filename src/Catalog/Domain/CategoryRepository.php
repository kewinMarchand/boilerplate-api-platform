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

    public function findBySlug(string $slug): ?Category;

    public function save(Category $category): void;

    public function remove(Category $category): void;
}
