<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

use App\Media\Domain\Image;

/**
 * Agrégat : un produit garde un prix positif ou nul (Price), une image envoyée par l'admin (ADR 0002) et une galerie
 * de 8 images au plus, ordonnées (ADR 0009).
 * « À la une » : montré dans le carrousel de la page d'accueil.
 * L'unicité du slug se vérifie dans les cas d'usage.
 */
class Product
{
    private ?int $id = null;
    public const int MAX_GALLERY_IMAGES = 8;

    private ?string $imageKey;
    private string $imageAlt;
    private ?int $imageWidth;

    /** @var list<array{key: string, alt: string, width: ?int}> images de la galerie, dans l'ordre (colonne JSON) */
    private array $gallery = [];

    /** Centimes, colonne entière : le value object est reconstruit à la lecture. */
    private int $price;

    /** Métadonnées de la fiche (SPEC-COMMUNE, section 26), vides tant que l'admin n'en a pas saisi. */
    private string $metaTitle = '';
    private string $metaDescription = '';

    /** @var array<string, string> valeurs des attributs renseignés, par code (colonne JSON) */
    private array $attributeValues = [];

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
        $this->imageWidth = $image->width;
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
        $this->imageWidth = $image->width;
        $this->featured = $featured;
        $this->description = self::normalize($description);
    }

    /**
     * @param array<string, string> $values par code d'attribut, déjà contrôlées par ProductAttribute::valueFor
     */
    public function changeAttributeValues(array $values): void
    {
        $this->attributeValues = $values;
    }

    public function changeMeta(string $title, string $description): void
    {
        $this->metaTitle = trim($title);
        $this->metaDescription = trim($description);
    }

    public function getMetaTitle(): string
    {
        return $this->metaTitle;
    }

    public function getMetaDescription(): string
    {
        return $this->metaDescription;
    }

    /**
     * @param list<Image> $images dans l'ordre d'affichage
     *
     * @throws CatalogRuleViolation plus de 8 images
     */
    public function changeGallery(array $images): void
    {
        self::checkGallerySize(\count($images));
        $this->gallery = array_map(static fn (Image $image): array => ['key' => $image->key, 'alt' => $image->alt, 'width' => $image->width], $images);
    }

    /**
     * @throws CatalogRuleViolation plus de 8 images
     */
    public static function checkGallerySize(int $count): void
    {
        if ($count > self::MAX_GALLERY_IMAGES) {
            throw new CatalogRuleViolation(\sprintf('La galerie compte %d images au plus (%d demandées).', self::MAX_GALLERY_IMAGES, $count));
        }
    }

    /**
     * @return list<Image> dans l'ordre d'affichage
     */
    public function getGallery(): array
    {
        return array_map(static fn (array $image): Image => new Image($image['key'], $image['alt'], null, $image['width']), $this->gallery);
    }

    public function removeAttributeValue(string $code): void
    {
        unset($this->attributeValues[$code]);
    }

    /**
     * @return array<string, string> par code d'attribut
     */
    public function getAttributeValues(): array
    {
        return $this->attributeValues;
    }

    public function getAttributeValue(string $code): ?string
    {
        return $this->attributeValues[$code] ?? null;
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
        return null === $this->imageKey ? null : new Image($this->imageKey, $this->imageAlt, null, $this->imageWidth);
    }

    /** Fins de ligne du formulaire (CRLF) ramenées à LF, blancs de début et de fin retirés. */
    private static function normalize(string $text): string
    {
        return trim(str_replace("\r\n", "\n", $text));
    }
}
