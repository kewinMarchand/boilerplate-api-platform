<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

final readonly class ProductSearchResult
{
    /**
     * @param list<Product>                                                        $items           produits de la page demandée
     * @param array<value-of<Exposure>, int>                                       $exposureCounts
     * @param array<value-of<Size>, int>                                           $sizeCounts
     * @param list<array{category: Category, count: int}>                          $categoryCounts  sous-catégories directes de la branche
     * @param list<array{attribute: ProductAttribute, counts: array<string, int>}> $attributeCounts attributs filtrables
     *                                                                                              portés dans la branche, toutes leurs valeurs
     */
    public function __construct(
        public array $items,
        public int $totalItems,
        public array $exposureCounts,
        public array $sizeCounts,
        public array $categoryCounts,
        public array $attributeCounts,
    ) {
    }
}
