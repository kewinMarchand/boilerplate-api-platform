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

/**
 * @implements ProviderInterface<ProductResource>
 */
final readonly class FeaturedProductsProvider implements ProviderInterface
{
    public function __construct(
        private ProductRepository $products,
        private ProductAttributeRepository $attributes,
        private ImageViews $images,
    ) {
    }

    /**
     * @return list<ProductResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $attributes = $this->attributes->findAll();

        return array_map(fn (Product $product): ProductResource => ProductResource::fromModel($product, $this->images, $attributes), $this->products->findFeatured());
    }
}
