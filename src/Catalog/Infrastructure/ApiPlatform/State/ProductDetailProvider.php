<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Catalog\Domain\ProductRepository;
use App\Catalog\Infrastructure\ApiPlatform\Resource\ProductDetailResource;
use App\Media\Infrastructure\ApiPlatform\ImageViews;

/**
 * Fiche d'un produit par son slug ; null (404) pour un slug inconnu.
 *
 * @implements ProviderInterface<ProductDetailResource>
 */
final readonly class ProductDetailProvider implements ProviderInterface
{
    public function __construct(
        private ProductRepository $products,
        private ImageViews $images,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?ProductDetailResource
    {
        $slug = $uriVariables['slug'] ?? null;
        $product = \is_string($slug) ? $this->products->findBySlug($slug) : null;

        return null === $product ? null : ProductDetailResource::fromModel($product, $this->images);
    }
}
