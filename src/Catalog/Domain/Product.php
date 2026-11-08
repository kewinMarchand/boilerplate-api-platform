<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

use App\Media\Domain\Image;

/**
 * Agrégat : un produit garde un prix positif ou nul (Price) et une image envoyée par l'admin (ADR 0002).
 * « À la une » : montré dans le carrousel de la page d'accueil.
 * L'unicité du slug se vérifie dans les cas d'usage.
 */
class Product
{
    private ?int $id = null;
    private ?string $imageKey;
    private string $imageAlt;

    /** Centimes, colonne entière : le value object est reconstruit à la lecture. */
    private int $price;

    public function __construct(
        private string $slug,
        private string $name,
        private Category $category,
        Price $price,
        private Exposure $exposure,
        private Size $size,
        private bool $inStock,
        Image $image,
        private bool $featured = false,
        private string $description = '',
    ) {
        $this->description = self::normalize($description);
        $this->price = $price->cents;
        $this->imageKey = $image->key;
        $this->imageAlt = $image->alt;
    }

    public function update(string $slug, string $name, Category $category, Price $price, Exposure $exposure, Size $size, bool $inStock, Image $image, bool $featured, string $description): void
    {
        $this->slug = $slug;
        $this->name = $name;
        $this->category = $category;
        $this->price = $price->cents;
        $this->exposure = $exposure;
        $this->size = $size;
        $this->inStock = $inStock;
        $this->imageKey = $image->key;
        $this->imageAlt = $image->alt;
        $this->featured = $featured;
        $this->description = self::normalize($description);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function getPrice(): Price
    {
        return Price::fromCents($this->price);
    }

    public function getExposure(): Exposure
    {
        return $this->exposure;
    }

    public function getSize(): Size
    {
        return $this->size;
    }

    public function isInStock(): bool
    {
        return $this->inStock;
    }

    public function isFeatured(): bool
    {
        return $this->featured;
    }

    /** Texte brut, paragraphes séparés par une ligne vide ; vide tant que l'admin n'en a pas écrit. */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Null pour un produit d'avant l'envoi des images (ligne migrée), jusqu'à ce que l'admin lui en donne une.
     */
    public function getImage(): ?Image
    {
        return null === $this->imageKey ? null : new Image($this->imageKey, $this->imageAlt);
    }

    /** Fins de ligne du formulaire (CRLF) ramenées à LF, blancs de début et de fin retirés. */
    private static function normalize(string $text): string
    {
        return trim(str_replace("\r\n", "\n", $text));
    }
}
