<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

interface ProductRepository
{
    /**
     * @return list<Product> tous les produits, dans l'ordre de pertinence
     */
    public function findAll(): array;

    public function find(int $id): ?Product;

    public function findBySlug(string $slug): ?Product;

    public function countInCategory(Category $category): int;

    public function save(Product $product): void;

    public function remove(Product $product): void;
}
