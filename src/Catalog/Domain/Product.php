<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

/**
 * Agrégat : un produit garde un prix positif ou nul (Price) et un visuel existant.
 * L'unicité du slug se vérifie dans les cas d'usage.
 */
class Product
{
    public const int IMAGE_COUNT = 12;

    private ?int $id = null;

    /** Centimes, colonne entière : le value object est reconstruit à la lecture. */
    private int $price;

    /**
     * @param int $image index du visuel partagé, de 1 à 12
     */
    public function __construct(
        private string $slug,
        private string $name,
        private Category $category,
        Price $price,
        private Exposure $exposure,
        private Size $size,
        private bool $inStock,
        private int $image,
    ) {
        self::guardImage($image);
        $this->price = $price->cents;
    }

    /**
     * @throws CatalogRuleViolation
     */
    public function update(string $slug, string $name, Category $category, Price $price, Exposure $exposure, Size $size, bool $inStock, int $image): void
    {
        self::guardImage($image);
        $this->slug = $slug;
        $this->name = $name;
        $this->category = $category;
        $this->price = $price->cents;
        $this->exposure = $exposure;
        $this->size = $size;
        $this->inStock = $inStock;
        $this->image = $image;
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

    public function getImage(): int
    {
        return $this->image;
    }

    private static function guardImage(int $image): void
    {
        if ($image < 1 || $image > self::IMAGE_COUNT) {
            throw new CatalogRuleViolation(\sprintf('Le visuel est un numéro de 1 à %d : %d.', self::IMAGE_COUNT, $image));
        }
    }
}
