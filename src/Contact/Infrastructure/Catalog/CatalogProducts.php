<?php

declare(strict_types=1);

namespace App\Contact\Infrastructure\Catalog;

use App\Catalog\Domain\ProductRepository;
use App\Contact\Domain\InquiredProduct;
use App\Contact\Domain\ProductCatalog;
use App\Shared\Infrastructure\Locale\ContentLocale;

/**
 * Lit le produit dans le catalogue : seul point de contact entre les deux modules (ADR 0014). Le slug envoyé est celui
 * de la langue de la requête (ADR 0015) ; le message garde le slug et le nom de la langue par défaut, celle du site.
 */
final readonly class CatalogProducts implements ProductCatalog
{
    public function __construct(
        private ProductRepository $products,
        private ContentLocale $contentLocale,
    ) {
    }

    public function findBySlug(string $slug): ?InquiredProduct
    {
        $product = $this->products->findBySlug($slug, $this->contentLocale->current());

        return null === $product ? null : new InquiredProduct($product->getSlug(), $product->getName());
    }
}
