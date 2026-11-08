<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Catalog\Application\SearchProducts;
use App\Catalog\Domain\AttributeChoice;
use App\Catalog\Infrastructure\ApiPlatform\Resource\AttributeFacet;
use App\Catalog\Infrastructure\ApiPlatform\Resource\AttributeFacetValue;
use App\Catalog\Infrastructure\ApiPlatform\Resource\CategoryFacetValue;
use App\Catalog\Infrastructure\ApiPlatform\Resource\FacetValue;
use App\Catalog\Infrastructure\ApiPlatform\Resource\ProductFacetsResource;

/**
 * @implements ProviderInterface<ProductFacetsResource>
 */
final readonly class ProductFacetsProvider implements ProviderInterface
{
    public function __construct(
        private SearchProducts $searchProducts,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ProductFacetsResource
    {
        $result = ($this->searchProducts)(ProductQuery::criteria($operation));

        return new ProductFacetsResource(
            $result->totalItems,
            $this->facetValues($result->exposureCounts),
            $this->facetValues($result->sizeCounts),
            array_map(
                static fn (array $entry): CategoryFacetValue => new CategoryFacetValue($entry['category']->getSlug(), $entry['category']->getName(), $entry['count']),
                $result->categoryCounts,
            ),
            array_map(
                static fn (array $entry): AttributeFacet => new AttributeFacet(
                    $entry['attribute']->getCode(),
                    $entry['attribute']->getLabel(),
                    array_map(
                        static fn (AttributeChoice $choice): AttributeFacetValue => new AttributeFacetValue($choice->value, $choice->label, $entry['counts'][$choice->value] ?? 0),
                        $entry['attribute']->getChoices(),
                    ),
                ),
                $result->attributeCounts,
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
}
