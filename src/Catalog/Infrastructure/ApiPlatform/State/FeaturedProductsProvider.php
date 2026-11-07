<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductRepository;
use App\Catalog\Infrastructure\ApiPlatform\Resource\ProductItem;
use App\Media\Infrastructure\ApiPlatform\ImageViews;

/**
 * @implements ProviderInterface<ProductItem>
 */
final readonly class FeaturedProductsProvider implements ProviderInterface
{
    public function __construct(
        private ProductRepository $products,
        private ImageViews $images,
    ) {
    }

    /**
     * @return list<ProductItem>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return array_map(fn (Product $product): ProductItem => ProductItem::fromModel($product, $this->images), $this->products->findFeatured());
    }
}
