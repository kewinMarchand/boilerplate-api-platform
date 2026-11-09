<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductAttribute;
use App\Catalog\Infrastructure\ApiPlatform\State\ProductDetailProvider;
use App\Media\Domain\Image;
use App\Media\Domain\ImageUsage;
use App\Media\Infrastructure\ApiPlatform\ImageView;
use App\Media\Infrastructure\ApiPlatform\ImageViews;
use App\Shared\Infrastructure\Locale\Alternate;

#[ApiResource(
    shortName: 'ProductDetail',
    description: 'Fiche d\'un produit : les champs de la liste, plus sa description, le chemin de sa catégorie, sa galerie, ses métadonnées et ses versions dans les autres langues.',
    // image peut valoir null : sans cela, API Platform retire la clé alors que le schéma la déclare requise
    normalizationContext: ['skip_null_values' => false],
    operations: [
        new Get(
            uriTemplate: '/products/{slug}',
            description: 'Lit un produit par son slug dans la langue demandée (le sien, ou celui de la langue par défaut s\'il n\'est pas traduit). Slug d\'une autre langue : 404.',
            provider: ProductDetailProvider::class,
        ),
    ],
)]
final readonly class ProductDetailResource
{
    /**
     * @param list<CategoryLink>   $categoryPath
     * @param list<AttributeValue> $attributes
     * @param list<ImageView>      $gallery
     * @param list<Alternate>      $alternates
     */
    public function __construct(
        #[ApiProperty(identifier: false, required: true)]
        public int $id,
        #[ApiProperty(identifier: true, required: true)]
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
        #[ApiProperty(description: 'Catégories de la racine à celle du produit, pour le fil d\'Ariane.', readableLink: true, required: true)]
        public array $categoryPath,
        #[ApiProperty(description: 'Texte brut, paragraphes séparés par une ligne vide ; chaîne vide si l\'admin n\'en a pas écrit.', required: true)]
        public string $description,
        #[ApiProperty(description: 'Attributs configurables renseignés sur le produit, par position ; tableau vide si aucun.', readableLink: true, required: true)]
        public array $attributes,
        #[ApiProperty(description: 'Images de la galerie (carrées, 8 au plus), dans l\'ordre, sans l\'image principale ; tableau vide si aucune.', readableLink: true, required: true)]
        public array $gallery,
        #[ApiProperty(description: 'Titre pour les moteurs de recherche, 60 caractères au plus ; chaîne vide si l\'admin n\'en a pas saisi.', required: true)]
        public string $metaTitle,
        #[ApiProperty(description: 'Description pour les moteurs de recherche, 160 caractères au plus ; chaîne vide si l\'admin n\'en a pas saisi.', required: true)]
        public string $metaDescription,
        #[ApiProperty(description: 'Le produit dans chaque langue activée, langue demandée comprise, pour les hreflang et le sélecteur de langue ; tableau vide sur un site monolingue.', readableLink: true, required: true)]
        public array $alternates,
    ) {
    }

    /**
     * @param list<ProductAttribute> $attributes définitions triées par position
     * @param list<Alternate>        $alternates
     */
    public static function fromModel(Product $product, ImageViews $images, array $attributes, string $locale, array $alternates): self
    {
        return new self(
            (int) $product->getId(),
            $product->getSlug($locale),
            $product->getName($locale),
            $product->getCategory()->getSlug($locale),
            $product->getPrice()->cents,
            $product->getExposure()->value,
            $product->getSize()->value,
            $product->isInStock(),
            $images->view($product->getImage($locale), ImageUsage::Product),
            CategoryLink::pathTo($product->getCategory(), $locale),
            $product->getDescription($locale),
            AttributeValue::listFor($product, $attributes, $locale),
            array_map(static fn (Image $image): ImageView => $images->view($image, ImageUsage::Product), $product->getGallery($locale)),
            $product->getMetaTitle($locale),
            $product->getMetaDescription($locale),
            $alternates,
        );
    }
}
