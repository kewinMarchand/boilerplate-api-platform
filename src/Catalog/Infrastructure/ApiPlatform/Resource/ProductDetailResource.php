<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Catalog\Domain\Product;
use App\Catalog\Infrastructure\ApiPlatform\State\ProductDetailProvider;
use App\Media\Domain\ImageUsage;
use App\Media\Infrastructure\ApiPlatform\ImageView;
use App\Media\Infrastructure\ApiPlatform\ImageViews;

#[ApiResource(
    shortName: 'ProductDetail',
    description: 'Fiche d\'un produit : les champs de la liste, plus sa description et le chemin de sa catégorie.',
    // image peut valoir null : sans cela, API Platform retire la clé alors que le schéma la déclare requise
    normalizationContext: ['skip_null_values' => false],
    operations: [
        new Get(
            uriTemplate: '/products/{slug}',
            description: 'Lit un produit par son slug.',
            provider: ProductDetailProvider::class,
        ),
    ],
)]
final readonly class ProductDetailResource
{
    /**
     * @param list<CategoryLink> $categoryPath
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
            CategoryLink::pathTo($product->getCategory()),
            $product->getDescription(),
        );
    }
}
