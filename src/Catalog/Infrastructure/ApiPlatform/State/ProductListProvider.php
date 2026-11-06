<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\State\ParameterNotFound;
use ApiPlatform\State\ProviderInterface;
use App\Catalog\Application\SearchProducts;
use App\Catalog\Domain\Exposure;
use App\Catalog\Domain\Price;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductCriteria;
use App\Catalog\Domain\ProductSort;
use App\Catalog\Domain\Size;
use App\Catalog\Infrastructure\ApiPlatform\Resource\CategoryFacetValue;
use App\Catalog\Infrastructure\ApiPlatform\Resource\FacetValue;
use App\Catalog\Infrastructure\ApiPlatform\Resource\ProductFacets;
use App\Catalog\Infrastructure\ApiPlatform\Resource\ProductItem;
use App\Catalog\Infrastructure\ApiPlatform\Resource\ProductListResource;
use App\Media\Infrastructure\ApiPlatform\ImageViews;

/**
 * @implements ProviderInterface<ProductListResource>
 */
final readonly class ProductListProvider implements ProviderInterface
{
    public const int MAX_ITEMS_PER_PAGE = 48;
    /** Borne qui garde le calcul de l'offset dans les entiers : au-delà, la page est simplement vide. */
    public const int MAX_PAGE = 1_000_000;

    public function __construct(
        private SearchProducts $searchProducts,
        private ImageViews $images,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ProductListResource
    {
        $criteria = $this->criteria($operation);
        $result = ($this->searchProducts)($criteria);

        return new ProductListResource(
            array_map(fn (Product $product): ProductItem => ProductItem::fromModel($product, $this->images), $result->items),
            $result->totalItems,
            $criteria->page,
            $criteria->itemsPerPage,
            (int) ceil($result->totalItems / $criteria->itemsPerPage),
            new ProductFacets(
                $this->facetValues($result->exposureCounts),
                $this->facetValues($result->sizeCounts),
                array_map(
                    static fn (array $entry): CategoryFacetValue => new CategoryFacetValue($entry['category']->getSlug(), $entry['category']->getName(), $entry['count']),
                    $result->categoryCounts,
                ),
            ),
        );
    }

    /**
     * @param array<string, int> $counts
     *
     * @return list<FacetValue>
     */
    private function facetValues(array $counts): array
    {
        $values = [];
        foreach ($counts as $value => $count) {
            $values[] = new FacetValue($value, $count);
        }

        return $values;
    }

    private function criteria(Operation $operation): ProductCriteria
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
            priceMin: $this->toPrice($value('priceMin')),
            priceMax: $this->toPrice($value('priceMax')),
            inStockOnly: \in_array($value('inStock'), [true, 'true', '1'], true),
            sort: match (true) {
                'asc' === $value('order[price]') => ProductSort::PriceAsc,
                'desc' === $value('order[price]') => ProductSort::PriceDesc,
                'asc' === $value('order[name]') => ProductSort::NameAsc,
                'desc' === $value('order[name]') => ProductSort::NameDesc,
                default => null,
            },
            page: min(self::MAX_PAGE, max(1, $this->toInt($value('page')) ?? 1)),
            itemsPerPage: $this->itemsPerPage($this->toInt($value('itemsPerPage'))),
        );
    }

    private function itemsPerPage(?int $itemsPerPage): int
    {
        return null !== $itemsPerPage && $itemsPerPage >= 1 && $itemsPerPage <= self::MAX_ITEMS_PER_PAGE
            ? $itemsPerPage
            : ProductCriteria::DEFAULT_ITEMS_PER_PAGE;
    }

    private function toPrice(mixed $value): ?Price
    {
        $cents = $this->toInt($value);

        return null === $cents ? null : Price::fromCents($cents);
    }

    /**
     * Les valeurs invalides sont ignorées, comme dans l'URL des fronts.
     */
    private function toInt(mixed $value): ?int
    {
        return \is_int($value) || (\is_string($value) && ctype_digit($value)) ? (int) $value : null;
    }
}
