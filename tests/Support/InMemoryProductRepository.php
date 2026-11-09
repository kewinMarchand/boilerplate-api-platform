<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Catalog\Domain\Category;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductRepository;

final class InMemoryProductRepository implements ProductRepository
{
    /** @var list<Product> */
    private array $products = [];

    /**
     * @param list<Product> $products enregistrés dans l'ordre, avec un identifiant attribué comme en base
     */
    public function __construct(array $products = [])
    {
        foreach ($products as $product) {
            $this->save($product);
        }
    }

    public function findAll(): array
    {
        return $this->products;
    }

    public function findFeatured(): array
    {
        $featured = array_values(array_filter($this->products, static fn (Product $product): bool => $product->isFeatured()));
        usort($featured, static fn (Product $a, Product $b): int => $a->getName() <=> $b->getName());

        return $featured;
    }

    public function find(int $id): ?Product
    {
        foreach ($this->products as $product) {
            if ($product->getId() === $id) {
                return $product;
            }
        }

        return null;
    }

    public function findBySlug(string $slug, ?string $locale = null): ?Product
    {
        foreach ($this->products as $product) {
            if ($product->getSlug($locale) === $slug) {
                return $product;
            }
        }

        return null;
    }

    public function countInCategory(Category $category): int
    {
        return \count(array_filter($this->products, static fn (Product $product): bool => $product->getCategory() === $category));
    }

    public function save(Product $product): void
    {
        if (null === $product->getId()) {
            Identity::assign($product, \count($this->products) + 1);
            $this->products[] = $product;
        }
    }

    public function remove(Product $product): void
    {
        $this->products = array_values(array_filter($this->products, static fn (Product $kept): bool => $kept !== $product));
    }
}
