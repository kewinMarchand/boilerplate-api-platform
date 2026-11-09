<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

/**
 * Filtre, compte, trie et pagine les produits en mémoire. Le catalogue de test tient en
 * quelques dizaines de lignes : un calcul en PHP garde les facettes disjonctives lisibles.
 *
 * Comptage disjonctif : le compteur d'une valeur de facette applique tous les filtres actifs
 * sauf ceux de sa propre facette, pour indiquer ce que donnerait l'ajout de cette valeur.
 */
final class ProductSearch
{
    /**
     * @param list<Product>          $products
     * @param list<ProductAttribute> $attributes triés par position
     */
    public function search(array $products, CategoryTree $tree, ProductCriteria $criteria, array $attributes): ProductSearchResult
    {
        $current = null === $criteria->categorySlug ? null : $tree->find($criteria->categorySlug, $criteria->locale);
        $branch = $tree->branchSlugs($current);
        $facets = array_values(array_filter($attributes, static fn (ProductAttribute $attribute): bool => $attribute->isFacet()));
        $attributeFilters = $this->attributeFilters($facets, $criteria->attributes);

        $inBranch = static fn (Product $product): bool => isset($branch[$product->getCategory()->getSlug()]);
        $matchesExposure = static fn (Product $product): bool => [] === $criteria->exposures || \in_array($product->getExposure(), $criteria->exposures, true);
        $matchesSize = static fn (Product $product): bool => [] === $criteria->sizes || \in_array($product->getSize(), $criteria->sizes, true);
        $matchesAttributes = static function (Product $product, ?string $exceptCode = null) use ($attributeFilters): bool {
            foreach ($attributeFilters as $code => $values) {
                if ($code !== $exceptCode && !\in_array($product->getAttributeValue($code), $values, true)) {
                    return false;
                }
            }

            return true;
        };
        $matchesPriceAndStock = static fn (Product $product): bool => $product->getPrice()->isBetween($criteria->priceMin, $criteria->priceMax)
            && (!$criteria->inStockOnly || $product->isInStock());
        $matchesOthers = static fn (Product $product): bool => $matchesPriceAndStock($product) && $matchesAttributes($product);

        $results = array_values(array_filter(
            $products,
            static fn (Product $product): bool => $inBranch($product) && $matchesExposure($product) && $matchesSize($product) && $matchesOthers($product),
        ));

        $exposureCounts = [];
        foreach (Exposure::cases() as $exposure) {
            $exposureCounts[$exposure->value] = \count(array_filter(
                $products,
                static fn (Product $product): bool => $product->getExposure() === $exposure && $inBranch($product) && $matchesSize($product) && $matchesOthers($product),
            ));
        }

        $sizeCounts = [];
        foreach (Size::cases() as $size) {
            $sizeCounts[$size->value] = \count(array_filter(
                $products,
                static fn (Product $product): bool => $product->getSize() === $size && $inBranch($product) && $matchesExposure($product) && $matchesOthers($product),
            ));
        }

        $categoryCounts = [];
        foreach ($tree->childrenOf($current) as $child) {
            $childBranch = $tree->branchSlugs($child);
            $categoryCounts[] = ['category' => $child, 'count' => \count(array_filter(
                $products,
                static fn (Product $product): bool => isset($childBranch[$product->getCategory()->getSlug()]) && $matchesExposure($product) && $matchesSize($product) && $matchesOthers($product),
            ))];
        }

        $attributeCounts = [];
        foreach ($facets as $attribute) {
            $code = $attribute->getCode();
            $carried = array_filter($products, static fn (Product $product): bool => $inBranch($product) && null !== $product->getAttributeValue($code));
            if ([] === $carried) {
                continue;
            }
            $counts = [];
            foreach ($attribute->getChoices() as $choice) {
                $counts[$choice->value] = \count(array_filter(
                    $carried,
                    static fn (Product $product): bool => $product->getAttributeValue($code) === $choice->value && $matchesExposure($product) && $matchesSize($product)
                        && $matchesPriceAndStock($product) && $matchesAttributes($product, $code),
                ));
            }
            $attributeCounts[] = ['attribute' => $attribute, 'counts' => $counts];
        }

        $sorted = $this->sort($results, $criteria->sort, $criteria->locale);
        $items = \array_slice($sorted, ($criteria->page - 1) * $criteria->itemsPerPage, $criteria->itemsPerPage);

        return new ProductSearchResult($items, \count($results), $exposureCounts, $sizeCounts, $categoryCounts, $attributeCounts);
    }

    /**
     * Garde les filtres d'attributs filtrables et leurs valeurs connues ; un attribut sans valeur connue ne filtre pas.
     *
     * @param list<ProductAttribute>      $facets
     * @param array<string, list<string>> $requested
     *
     * @return array<string, list<string>>
     */
    private function attributeFilters(array $facets, array $requested): array
    {
        $filters = [];
        foreach ($facets as $attribute) {
            $known = array_map(static fn (AttributeChoice $choice): string => $choice->value, $attribute->getChoices());
            $values = array_values(array_intersect($requested[$attribute->getCode()] ?? [], $known));
            if ([] !== $values) {
                $filters[$attribute->getCode()] = $values;
            }
        }

        return $filters;
    }

    /**
     * @param list<Product> $products
     *
     * @return list<Product>
     */
    private function sort(array $products, ?ProductSort $sort, ?string $locale): array
    {
        if (null === $sort) {
            return $products;
        }

        // Noms dans la langue demandée, comparés selon ses règles (le français par défaut)
        $collator = new \Collator($locale ?? 'fr');
        usort($products, static fn (Product $a, Product $b): int => match ($sort) {
            ProductSort::PriceAsc => $a->getPrice()->compareTo($b->getPrice()),
            ProductSort::PriceDesc => $b->getPrice()->compareTo($a->getPrice()),
            ProductSort::NameAsc => (int) $collator->compare($a->getName($locale), $b->getName($locale)),
            ProductSort::NameDesc => (int) $collator->compare($b->getName($locale), $a->getName($locale)),
        });

        return $products;
    }
}
