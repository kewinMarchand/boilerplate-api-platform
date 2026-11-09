<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Parameter;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductAttribute;
use App\Catalog\Domain\ProductCriteria;
use App\Catalog\Infrastructure\ApiPlatform\State\FeaturedProductsProvider;
use App\Catalog\Infrastructure\ApiPlatform\State\ProductCollectionProvider;
use App\Media\Domain\ImageUsage;
use App\Media\Infrastructure\ApiPlatform\ImageView;
use App\Media\Infrastructure\ApiPlatform\ImageViews;

/**
 * Produit tel que le montrent la liste du catalogue et le carrousel de l'accueil.
 * Les filtres sont repris tels quels par ProductFacetsResource : un attribut n'accepte pas d'appel de fonction.
 */
#[ApiResource(
    shortName: 'Product',
    description: 'Produit du catalogue, sans sa description ni le chemin de sa catégorie (voir ProductDetail).',
    // image peut valoir null : sans cela, API Platform retire la clé alors que le schéma la déclare requise
    normalizationContext: ['skip_null_values' => false],
    operations: [
        new GetCollection(
            uriTemplate: '/products',
            description: 'Liste une page de produits. Les filtres se combinent en ET, les valeurs d\'une même facette en OU. Une valeur invalide (inconnue, non numérique, hors bornes) est ignorée et remplacée par la valeur par défaut. Le total et les compteurs de facettes : GET /api/product-facets avec les mêmes filtres.',
            provider: ProductCollectionProvider::class,
            parameters: [
                'category' => new QueryParameter(
                    description: 'Slug de catégorie : inclut toute la branche. Slug inconnu : 404.',
                    schema: ['type' => 'string'],
                ),
                'exposure' => new QueryParameter(
                    description: 'Expositions acceptées, clé répétée : exposure[]=soleil&exposure[]=ombre.',
                    castToArray: true,
                    schema: ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['soleil', 'mi-ombre', 'ombre']]],
                ),
                'size' => new QueryParameter(
                    description: 'Tailles acceptées, clé répétée : size[]=S&size[]=M.',
                    castToArray: true,
                    schema: ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['S', 'M', 'L']]],
                ),
                'attributes' => new QueryParameter(
                    description: 'Valeurs acceptées des attributs filtrables, par code, clé répétée : attributes[feuillage][]=persistant&attributes[feuillage][]=caduc. Valeurs d\'un même attribut en OU, attributs entre eux en ET. Code ou valeur inconnus : ignorés. Les attributs filtrables et leurs valeurs : attributes de GET /api/product-facets.',
                    schema: ['type' => 'object', 'additionalProperties' => ['type' => 'array', 'items' => ['type' => 'string']]],
                    openApi: new Parameter('attributes', 'query', style: 'deepObject', explode: true),
                ),
                'priceMin' => new QueryParameter(
                    description: 'Prix minimum inclus, en centimes.',
                    schema: ['type' => 'integer'],
                ),
                'priceMax' => new QueryParameter(
                    description: 'Prix maximum inclus, en centimes.',
                    schema: ['type' => 'integer'],
                ),
                'inStock' => new QueryParameter(
                    description: 'true : uniquement les produits en stock.',
                    schema: ['type' => 'boolean'],
                ),
                'order[price]' => new QueryParameter(
                    description: 'Tri par prix, asc ou desc. Prioritaire sur order[name]. Sans tri : ordre de pertinence.',
                    schema: ['type' => 'string'],
                ),
                'order[name]' => new QueryParameter(
                    description: 'Tri alphabétique (collation française), asc ou desc.',
                    schema: ['type' => 'string'],
                ),
                'page' => new QueryParameter(
                    description: 'Numéro de page, à partir de 1. Au-delà de la dernière page : tableau vide.',
                    schema: ['type' => 'integer', 'default' => 1],
                ),
                'itemsPerPage' => new QueryParameter(
                    description: 'Produits par page, de 1 à 48.',
                    schema: ['type' => 'integer', 'default' => ProductCriteria::DEFAULT_ITEMS_PER_PAGE],
                ),
            ],
        ),
        // Sous /products, « featured » se lirait comme un slug de produit
        new GetCollection(
            uriTemplate: '/featured-products',
            description: 'Liste les produits à la une (carrousel de l\'accueil) par ordre alphabétique, sans pagination.',
            provider: FeaturedProductsProvider::class,
        ),
    ],
)]
final readonly class ProductResource
{
    /**
     * @param list<AttributeValue> $attributes
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
        #[ApiProperty(description: 'Attributs configurables renseignés sur le produit, par position ; tableau vide si aucun.', readableLink: true, required: true)]
        public array $attributes,
    ) {
    }

    /**
     * @param list<ProductAttribute> $attributes définitions triées par position
     */
    public static function fromModel(Product $product, ImageViews $images, array $attributes, string $locale): self
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
            AttributeValue::listFor($product, $attributes, $locale),
        );
    }
}
