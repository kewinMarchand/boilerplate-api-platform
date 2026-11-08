<?php

declare(strict_types=1);

namespace App\Contact\Infrastructure\Catalog;

use App\Catalog\Domain\ProductRepository;
use App\Contact\Domain\InquiredProduct;
use App\Contact\Domain\ProductCatalog;

/**
 * Lit le produit dans le catalogue : seul point de contact entre les deux modules (ADR 0014).
 */
final readonly class CatalogProducts implements ProductCatalog
{
    public function __construct(private ProductRepository $products)
    {
    }

    public function findBySlug(string $slug): ?InquiredProduct
    {
        $product = $this->products->findBySlug($slug);

        return null === $product ? null : new InquiredProduct($product->getSlug(), $product->getName());
    }
}
