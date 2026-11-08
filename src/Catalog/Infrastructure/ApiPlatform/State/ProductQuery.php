<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\State\ParameterNotFound;
use App\Catalog\Domain\Exposure;
use App\Catalog\Domain\Price;
use App\Catalog\Domain\ProductCriteria;
use App\Catalog\Domain\ProductSort;
use App\Catalog\Domain\Size;

/**
 * Lit les filtres, le tri et la pagination de la requête, communs à la liste des produits et à leurs facettes.
 * Les valeurs invalides sont ignorées, comme dans l'URL des fronts.
 */
final class ProductQuery
{
    public const int MAX_ITEMS_PER_PAGE = 48;
    /** Borne qui garde le calcul de l'offset dans les entiers : au-delà, la page est simplement vide. */
    public const int MAX_PAGE = 1_000_000;

    public static function criteria(Operation $operation): ProductCriteria
    {
        $value = static function (string $key) use ($operation): mixed {
            $value = $operation->getParameters()?->get($key, QueryParameter::class)?->getValue();

            return $value instanceof ParameterNotFound ? null : $value;
        };

        return new ProductCriteria(
            categorySlug: \is_string($category = $value('category')) && '' !== $category ? $category : null,
            exposures: array_values(array_filter(array_map(
                static fn (mixed $exposure): ?Exposure => \is_string($exposure) ? Exposure::tryFrom($exposure) : null,
                (array) $value('exposure'),
            ))),
            sizes: array_values(array_filter(array_map(
                static fn (mixed $size): ?Size => \is_string($size) ? Size::tryFrom($size) : null,
                (array) $value('size'),
            ))),
            priceMin: self::toPrice($value('priceMin')),
            priceMax: self::toPrice($value('priceMax')),
            inStockOnly: \in_array($value('inStock'), [true, 'true', '1'], true),
            sort: match (true) {
                'asc' === $value('order[price]') => ProductSort::PriceAsc,
                'desc' === $value('order[price]') => ProductSort::PriceDesc,
                'asc' === $value('order[name]') => ProductSort::NameAsc,
                'desc' === $value('order[name]') => ProductSort::NameDesc,
                default => null,
            },
            page: min(self::MAX_PAGE, max(1, self::toInt($value('page')) ?? 1)),
            itemsPerPage: self::itemsPerPage(self::toInt($value('itemsPerPage'))),
            attributes: self::attributeFilters($value('attributes')),
        );
    }

    /**
     * attributes[<code>][]=<valeur> ; une valeur seule (attributes[<code>]=<valeur>) est acceptée, comme exposure=ombre.
     *
     * @return array<string, list<string>>
     */
    private static function attributeFilters(mixed $value): array
    {
        $filters = [];
        foreach (\is_array($value) ? $value : [] as $code => $values) {
            if (\is_string($code)) {
                $filters[$code] = array_values(array_filter((array) $values, \is_string(...)));
            }
        }

        return $filters;
    }

    private static function itemsPerPage(?int $itemsPerPage): int
    {
        return null !== $itemsPerPage && $itemsPerPage >= 1 && $itemsPerPage <= self::MAX_ITEMS_PER_PAGE
            ? $itemsPerPage
            : ProductCriteria::DEFAULT_ITEMS_PER_PAGE;
    }

    private static function toPrice(mixed $value): ?Price
    {
        $cents = self::toInt($value);

        return null === $cents ? null : Price::fromCents($cents);
    }

    private static function toInt(mixed $value): ?int
    {
        return \is_int($value) || (\is_string($value) && ctype_digit($value)) ? (int) $value : null;
    }
}
