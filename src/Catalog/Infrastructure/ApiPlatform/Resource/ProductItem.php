<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use App\Catalog\Domain\Product;
use App\Media\Domain\ImageUsage;
use App\Media\Infrastructure\ApiPlatform\ImageView;
use App\Media\Infrastructure\ApiPlatform\ImageViews;

final readonly class ProductItem
{
    public function __construct(
        #[ApiProperty(required: true)]
        public int $id,
        #[ApiProperty(required: true)]
        public string $slug,
        #[ApiProperty(required: true)]
        public string $name,
        #[ApiProperty(required: true)]
        public string $categorySlug,
        #[ApiProperty(description: 'Prix en centimes.', required: true)]
        public int $price,
        #[ApiProperty(required: true, schema: ['type' => 'string', 'enum' => ['soleil', 'mi-ombre', 'ombre']])]
        public string $exposure,
        #[ApiProperty(required: true, schema: ['type' => 'string', 'enum' => ['S', 'M', 'L']])]
        public string $size,
        #[ApiProperty(required: true)]
        public bool $inStock,
        #[ApiProperty(description: 'Image du produit (carrée), null tant que l\'admin n\'en a pas envoyé.', required: true)]
        public ?ImageView $image,
    ) {
    }

    public static function fromModel(Product $product, ImageViews $images): self
    {
        return new self(
            (int) $product->getId(),
            $product->getSlug(),
            $product->getName(),
            $product->getCategory()->getSlug(),
            $product->getPrice()->cents,
            $product->getExposure()->value,
            $product->getSize()->value,
            $product->isInStock(),
            $images->view($product->getImage(), ImageUsage::Product),
        );
    }
}
