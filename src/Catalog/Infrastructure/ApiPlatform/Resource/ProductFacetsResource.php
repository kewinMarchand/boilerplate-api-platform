<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use App\Catalog\Domain\Exposure;
use App\Catalog\Domain\Size;
use App\Catalog\Infrastructure\ApiPlatform\State\ProductFacetsProvider;
use App\Shared\Infrastructure\ApiPlatform\Scenario\EmptyScenarioResult;

/**
 * Résumé des produits filtrés : leur total et les compteurs de facettes, calculés sur les mêmes filtres que GET /api/products.
 * Ressource à part : la liste est un tableau, qui ne porte ni total ni compteurs.
 */
#[ApiResource(
    shortName: 'ProductFacets',
    description: 'Total des produits filtrés et compteurs de facettes disjonctifs.',
    operations: [
        new Get(
            uriTemplate: '/product-facets',
            description: 'Compte les produits filtrés, au total et par valeur de facette. Mêmes filtres que GET /api/products, sans tri ni pagination.',
            provider: ProductFacetsProvider::class,
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
            ],
        ),
    ],
)]
final readonly class ProductFacetsResource implements EmptyScenarioResult
{
    /**
     * @param list<FacetValue>         $exposure
     * @param list<FacetValue>         $size
     * @param list<CategoryFacetValue> $category
     */
    public function __construct(
        #[ApiProperty(description: 'Nombre de produits filtrés, toutes pages confondues.', required: true)]
        public int $totalItems,
        #[ApiProperty(description: 'Toutes les expositions, y compris à 0.', required: true)]
        public array $exposure,
        #[ApiProperty(description: 'Toutes les tailles, y compris à 0.', required: true)]
        public array $size,
        #[ApiProperty(description: 'Sous-catégories directes de la catégorie filtrée (catégories racines sans filtre). Vide sur une feuille.', required: true)]
        public array $category,
    ) {
    }

    public static function emptyScenarioResult(): static
    {
        $zero = static fn (\BackedEnum $case): FacetValue => new FacetValue((string) $case->value, 0);

        return new static(0, array_map($zero, Exposure::cases()), array_map($zero, Size::cases()), []);
    }
}
