<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

interface ProductRepository
{
    /**
     * @return list<Product> tous les produits, dans l'ordre de pertinence
     */
    public function findAll(): array;

    /**
     * @return list<Product> produits à la une, par nom
     */
    public function findFeatured(): array;

    public function find(int $id): ?Product;

    /**
     * @param string|null $locale langue du slug (le sien, ou celui de la langue par défaut s'il n'est pas traduit) ;
     *                            null : la langue par défaut
     */
    public function findBySlug(string $slug, ?string $locale = null): ?Product;

    public function countInCategory(Category $category): int;

    public function save(Product $product): void;

    public function remove(Product $product): void;
}
