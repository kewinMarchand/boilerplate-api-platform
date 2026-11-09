<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Catalog\Domain\ProductAttributeRepository;
use App\Catalog\Domain\ProductRepository;
use App\Catalog\Infrastructure\ApiPlatform\Resource\ProductDetailResource;
use App\Media\Infrastructure\ApiPlatform\ImageViews;
use App\Shared\Infrastructure\Locale\ContentLocale;

/**
 * Fiche d'un produit par son slug dans la langue demandée ; null (404) pour un slug inconnu dans cette langue.
 *
 * @implements ProviderInterface<ProductDetailResource>
 */
final readonly class ProductDetailProvider implements ProviderInterface
{
    public function __construct(
        private ProductRepository $products,
        private ProductAttributeRepository $attributes,
        private ImageViews $images,
        private ContentLocale $contentLocale,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?ProductDetailResource
    {
        $slug = $uriVariables['slug'] ?? null;
        $locale = $this->contentLocale->current();
        $product = \is_string($slug) ? $this->products->findBySlug($slug, $locale) : null;

        return null === $product
            ? null
            : ProductDetailResource::fromModel($product, $this->images, $this->attributes->findAll(), $locale, $this->contentLocale->alternates($product->getSlug(...)));
    }
}
