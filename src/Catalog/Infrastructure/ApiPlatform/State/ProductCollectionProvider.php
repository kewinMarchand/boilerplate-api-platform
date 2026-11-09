<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use App\Catalog\Application\SearchProducts;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductAttributeRepository;
use App\Catalog\Infrastructure\ApiPlatform\Resource\ProductResource;
use App\Media\Infrastructure\ApiPlatform\ImageViews;
use App\Shared\Infrastructure\Locale\ContentLocale;

/**
 * @implements ProviderInterface<ProductResource>
 */
final readonly class ProductCollectionProvider implements ProviderInterface
{
    public function __construct(
        private SearchProducts $searchProducts,
        private ProductAttributeRepository $attributes,
        private ImageViews $images,
        private ContentLocale $contentLocale,
    ) {
    }

    /**
     * Un paginateur, pour que le JSON-LD porte le total filtré et les liens de pages ; le JSON simple reste un tableau.
     *
     * @return TraversablePaginator<ProductResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TraversablePaginator
    {
        $locale = $this->contentLocale->current();
        $criteria = ProductQuery::criteria($operation, $locale);
        $result = ($this->searchProducts)($criteria);
        $attributes = $this->attributes->findAll();

        return new TraversablePaginator(
            new \ArrayIterator(array_map(fn (Product $product): ProductResource => ProductResource::fromModel($product, $this->images, $attributes, $locale), $result->items)),
            $criteria->page,
            $criteria->itemsPerPage,
            $result->totalItems,
        );
    }
}
