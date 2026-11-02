<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\Catalog\Domain\Exposure;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\Size;

/**
 * Saisie d'un produit, remplie par l'admin. La forme (slug, longueurs) est validée à la frontière.
 */
final class ProductData
{
    public string $slug = '';
    public string $name = '';
    public ?int $categoryId = null;
    /** Centimes d'euro. */
    public int $price = 0;
    public Exposure $exposure = Exposure::Sun;
    public Size $size = Size::Medium;
    public bool $inStock = true;
    public int $image = 1;

    public static function fromProduct(Product $product): self
    {
        $data = new self();
        $data->slug = $product->getSlug();
        $data->name = $product->getName();
        $data->categoryId = $product->getCategory()->getId();
        $data->price = $product->getPrice()->cents;
        $data->exposure = $product->getExposure();
        $data->size = $product->getSize();
        $data->inStock = $product->isInStock();
        $data->image = $product->getImage();

        return $data;
    }
}
