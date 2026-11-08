<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\Catalog\Domain\Exposure;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\Size;
use App\Media\Domain\Image;

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
    public bool $featured = false;
    public string $description = '';
    /** Contenu du fichier envoyé, null pour garder l'image actuelle. */
    public ?string $imageContent = null;
    /** Vide (et non décorative) : le nom du produit. */
    public string $imageAlt = '';
    public bool $imageDecorative = false;
    public ?Image $currentImage = null;
    /** @var array<string, ?string> valeur saisie par code d'attribut, vide ou null quand il n'est pas renseigné */
    public array $attributes = [];
    public string $metaTitle = '';
    public string $metaDescription = '';
    /** @var list<GalleryImageData> images de la galerie, puis lignes d'ajout */
    public array $gallery = [];

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
        $data->featured = $product->isFeatured();
        $data->description = $product->getDescription();
        $data->currentImage = $product->getImage();
        $data->imageAlt = $data->currentImage->alt ?? '';
        $data->imageDecorative = $data->currentImage?->isDecorative() ?? false;
        $data->attributes = $product->getAttributeValues();
        $data->metaTitle = $product->getMetaTitle();
        $data->metaDescription = $product->getMetaDescription();
        foreach ($product->getGallery() as $index => $image) {
            $data->gallery[] = GalleryImageData::fromImage($image, $index + 1);
        }

        return $data;
    }
}
