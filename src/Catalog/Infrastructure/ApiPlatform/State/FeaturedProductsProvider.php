<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductAttributeRepository;
use App\Catalog\Domain\ProductRepository;
use App\Catalog\Infrastructure\ApiPlatform\Resource\ProductResource;
use App\Media\Infrastructure\ApiPlatform\ImageViews;
use App\Shared\Infrastructure\Locale\ContentLocale;

/**
 * @implements ProviderInterface<ProductResource>
 */
final readonly class FeaturedProductsProvider implements ProviderInterface
{
    public function __construct(
        private ProductRepository $products,
        private ProductAttributeRepository $attributes,
        private ImageViews $images,
        private ContentLocale $contentLocale,
    ) {
    }

    /**
     * @return list<ProductResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $attributes = $this->attributes->findAll();
        $locale = $this->contentLocale->current();

        // Ordre du nom dans la langue par défaut, quelle que soit la langue demandée
        return array_map(fn (Product $product): ProductResource => ProductResource::fromModel($product, $this->images, $attributes, $locale), $this->products->findFeatured());
    }
}
