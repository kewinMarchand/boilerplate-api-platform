<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use App\Catalog\Domain\Exposure;
use App\Catalog\Domain\ProductCriteria;
use App\Catalog\Domain\Size;
use App\Catalog\Infrastructure\ApiPlatform\State\ProductListProvider;
use App\Shared\Infrastructure\ApiPlatform\Scenario\EmptyScenarioResult;

/**
 * Une page de produits, son total et les compteurs de facettes, calculés sur les mêmes filtres.
 * Opération Get (et non GetCollection) : la réponse est un objet, pas une collection.
 */
#[ApiResource(
    shortName: 'ProductList',
    description: 'Page de produits filtrés, avec le total et les compteurs de facettes disjonctifs.',
    operations: [
        new Get(
            uriTemplate: '/products',
            description: 'Liste les produits du catalogue. Les filtres se combinent en ET, les valeurs d\'une même facette en OU. Une valeur invalide (inconnue, non numérique, hors bornes) est ignorée et remplacée par la valeur par défaut.',
            provider: ProductListProvider::class,
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
                    description: 'Numéro de page, à partir de 1. Au-delà de la dernière page : items vide.',
                    schema: ['type' => 'integer', 'default' => 1],
                ),
                'itemsPerPage' => new QueryParameter(
                    description: 'Produits par page, de 1 à 48.',
                    schema: ['type' => 'integer', 'default' => ProductCriteria::DEFAULT_ITEMS_PER_PAGE],
                ),
            ],
        ),
    ],
)]
final readonly class ProductListResource implements EmptyScenarioResult
{
    /**
     * @param list<ProductItem> $items
     */
    public function __construct(
        #[ApiProperty(required: true)]
        public array $items,
        #[ApiProperty(description: 'Nombre total de produits filtrés, toutes pages confondues.', required: true)]
        public int $totalItems,
        #[ApiProperty(required: true)]
        public int $page,
        #[ApiProperty(required: true)]
        public int $itemsPerPage,
        #[ApiProperty(description: 'Nombre de pages, 0 si aucun résultat.', required: true)]
        public int $totalPages,
        #[ApiProperty(required: true)]
        public ProductFacets $facets,
    ) {
    }

    public static function emptyScenarioResult(): static
    {
        $zero = static fn (\BackedEnum $case): FacetValue => new FacetValue((string) $case->value, 0);

        return new static(
            [],
            0,
            1,
            ProductCriteria::DEFAULT_ITEMS_PER_PAGE,
            0,
            new ProductFacets(array_map($zero, Exposure::cases()), array_map($zero, Size::cases()), []),
        );
    }
}
