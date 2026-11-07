<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Catalog\Infrastructure\ApiPlatform\State\FeaturedProductsProvider;

/**
 * Produits à la une, servis sous la forme des éléments de la liste du catalogue (ProductItem).
 * Ressource à part (/featured-products) : sous /products, « featured » se lirait comme un slug de produit.
 */
#[ApiResource(
    shortName: 'FeaturedProduct',
    description: 'Produits à la une, montrés dans le carrousel de la page d\'accueil.',
    normalizationContext: ['skip_null_values' => false],
    operations: [
        new GetCollection(
            uriTemplate: '/featured-products',
            description: 'Liste les produits à la une par ordre alphabétique, sans pagination.',
            output: ProductItem::class,
            provider: FeaturedProductsProvider::class,
        ),
    ],
)]
final readonly class FeaturedProductResource
{
}
