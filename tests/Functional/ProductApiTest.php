<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use ApiPlatform\Test\ApiTestCase;
use App\Media\Domain\ImageUsage;
use App\Tests\Support\TestImages;

/**
 * Valeurs attendues calculées à la main sur les 24 produits de CatalogFixtures.
 */
final class ProductApiTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = false;

    public function testListsFirstPageOfProductsWithoutFilter(): void
    {
        $items = $this->products('');

        self::assertCount(12, $items);
        self::assertSame(['id' => 1, 'slug' => 'monstera-deliciosa', 'name' => 'Monstera deliciosa', 'categorySlug' => 'monstera', 'price' => 3490, 'exposure' => 'mi-ombre', 'size' => 'M', 'inStock' => true, 'image' => TestImages::demoView('product-1', ImageUsage::Product, 'Monstera deliciosa')], $items[0]);
    }

    public function testFacetsCountAllProductsWithoutFilter(): void
    {
        $facets = $this->facets('');

        self::assertSame(24, $facets['totalItems']);
        self::assertSame(['soleil' => 14, 'mi-ombre' => 7, 'ombre' => 3], self::counts($facets, 'exposure'));
        self::assertSame(['S' => 6, 'M' => 9, 'L' => 9], self::counts($facets, 'size'));
        self::assertSame(['plantes-interieur' => 12, 'plantes-exterieur' => 9, 'plantes-aquatiques' => 3], self::categoryCounts($facets));
    }

    public function testCategoryFilterIncludesTheWholeBranch(): void
    {
        $facets = $this->facets('category=plantes-interieur');

        self::assertSame(12, $facets['totalItems']);
        self::assertCount(12, $this->products('category=plantes-interieur&itemsPerPage=48'));
        self::assertSame(['feuillages' => 6, 'plantes-a-fleurs' => 6], self::categoryCounts($facets));
    }

    public function testCategoryFacetListsDirectChildrenOfTheBranch(): void
    {
        $facets = $this->facets('category=feuillages');

        self::assertSame(6, $facets['totalItems']);
        self::assertSame(['monstera' => 3, 'fougeres' => 3], self::categoryCounts($facets));
        self::assertSame(['soleil' => 0, 'mi-ombre' => 4, 'ombre' => 2], self::counts($facets, 'exposure'));
    }

    public function testLeafCategoryHasNoCategoryFacet(): void
    {
        $facets = $this->facets('category=monstera');

        self::assertSame(3, $facets['totalItems']);
        self::assertSame([], $facets['category']);
    }

    public function testUnknownCategoryReturnsNotFoundOnBothResources(): void
    {
        foreach (['/api/products?category=cactus', '/api/product-facets?category=cactus'] as $uri) {
            static::createClient()->request('GET', $uri, ['headers' => ['Accept' => 'application/json']]);

            self::assertResponseStatusCodeSame(404);
            self::assertJsonContains(['detail' => 'Catégorie introuvable : cactus']);
        }
    }

    public function testExposureFilterKeepsItsOwnFacetCountsDisjunctive(): void
    {
        $facets = $this->facets('exposure[]=ombre');

        self::assertSame(3, $facets['totalItems']);
        self::assertCount(3, $this->products('exposure[]=ombre'));
        self::assertSame(['soleil' => 14, 'mi-ombre' => 7, 'ombre' => 3], self::counts($facets, 'exposure'));
        self::assertSame(['S' => 2, 'M' => 1, 'L' => 0], self::counts($facets, 'size'));
        self::assertSame(['plantes-interieur' => 3, 'plantes-exterieur' => 0, 'plantes-aquatiques' => 0], self::categoryCounts($facets));
    }

    public function testValuesOfOneFacetAreCombinedWithOr(): void
    {
        self::assertSame(10, $this->facets('exposure[]=ombre&exposure[]=mi-ombre')['totalItems']);
        self::assertCount(10, $this->products('exposure[]=ombre&exposure[]=mi-ombre&itemsPerPage=48'));
    }

    public function testSizeFilterKeepsItsOwnFacetCountsDisjunctive(): void
    {
        $facets = $this->facets('size[]=L');

        self::assertSame(9, $facets['totalItems']);
        self::assertSame(['S' => 6, 'M' => 9, 'L' => 9], self::counts($facets, 'size'));
        self::assertSame(['soleil' => 6, 'mi-ombre' => 3, 'ombre' => 0], self::counts($facets, 'exposure'));
    }

    public function testCombinedFacetsCountEachOneAgainstTheOthers(): void
    {
        $facets = $this->facets('exposure[]=soleil&size[]=S');

        self::assertSame(3, $facets['totalItems']);
        self::assertSame(['soleil' => 3, 'mi-ombre' => 1, 'ombre' => 2], self::counts($facets, 'exposure'));
        self::assertSame(['S' => 3, 'M' => 5, 'L' => 6], self::counts($facets, 'size'));
    }

    public function testPriceRangeIsInclusiveAndInCents(): void
    {
        self::assertSame([2490, 2690, 2990, 2290, 2490], array_column($this->products('priceMin=2290&priceMax=2990'), 'price'));
        self::assertSame(5, $this->facets('priceMin=2290&priceMax=2990')['totalItems']);
    }

    public function testInStockFilter(): void
    {
        self::assertSame(19, $this->facets('inStock=true')['totalItems']);
        self::assertNotContains(false, array_column($this->products('inStock=true&itemsPerPage=48'), 'inStock'));
    }

    public function testSortsByPrice(): void
    {
        self::assertSame([1290, 1490], \array_slice(array_column($this->products('order[price]=asc'), 'price'), 0, 2));
        self::assertSame([12900, 8990], \array_slice(array_column($this->products('order[price]=desc'), 'price'), 0, 2));
    }

    public function testSortsByNameWithFrenchCollation(): void
    {
        self::assertSame('anthurium-andreanum-rouge', $this->products('order[name]=asc')[0]['slug']);
        self::assertSame('strelitzia-reginae', $this->products('order[name]=desc')[0]['slug']);
    }

    public function testPaginates(): void
    {
        $second = $this->products('page=2');
        self::assertSame(13, $second[0]['id']);
        self::assertCount(12, $second);

        self::assertCount(4, $this->products('itemsPerPage=5&page=5'));
        self::assertSame([], $this->products('page=3'));
    }

    public function testJsonLdCollectionCarriesTheFilteredTotalAndPageLinks(): void
    {
        $response = static::createClient()->request('GET', '/api/products?itemsPerPage=5&page=2', ['headers' => ['Accept' => 'application/ld+json']]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            '@type' => 'Collection',
            'totalItems' => 24,
            'member' => [['@id' => '/api/products/fougere-arborescente-dicksonia', '@type' => 'Product']],
            'view' => ['last' => '/api/products?itemsPerPage=5&page=5', 'next' => '/api/products?itemsPerPage=5&page=3'],
        ]);
        /** @var array{member: list<mixed>} $body */
        $body = $response->toArray();
        self::assertCount(5, $body['member']);
    }

    public function testIgnoresInvalidValues(): void
    {
        $query = 'exposure[]=lune&size[]=XL&priceMin=abc&inStock=peut-etre&order[price]=haut&page=0&itemsPerPage=500';

        $items = $this->products($query);
        self::assertCount(12, $items);
        self::assertSame(1, $items[0]['id']);
        self::assertSame(24, $this->facets($query)['totalItems']);

        self::assertSame([], $this->products('page=99999999999999999999'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function products(string $query): array
    {
        $response = static::createClient()->request('GET', '/api/products?'.$query, ['headers' => ['Accept' => 'application/json']]);
        self::assertResponseIsSuccessful();

        /** @var list<array<string, mixed>> $items */
        $items = $response->toArray();

        return $items;
    }

    /**
     * @return array{totalItems: int, exposure: list<array{value: string, count: int}>, size: list<array{value: string, count: int}>, category: list<array{slug: string, name: string, count: int}>}
     */
    private function facets(string $query): array
    {
        $response = static::createClient()->request('GET', '/api/product-facets?'.$query, ['headers' => ['Accept' => 'application/json']]);
        self::assertResponseIsSuccessful();

        /** @var array{totalItems: int, exposure: list<array{value: string, count: int}>, size: list<array{value: string, count: int}>, category: list<array{slug: string, name: string, count: int}>} $facets */
        $facets = $response->toArray();

        return $facets;
    }

    /**
     * @param array{exposure: list<array{value: string, count: int}>, size: list<array{value: string, count: int}>} $facets
     * @param 'exposure'|'size'                                                                                     $facet
     *
     * @return array<string, int>
     */
    private static function counts(array $facets, string $facet): array
    {
        return array_column($facets[$facet], 'count', 'value');
    }

    /**
     * @param array{category: list<array{slug: string, name: string, count: int}>} $facets
     *
     * @return array<string, int>
     */
    private static function categoryCounts(array $facets): array
    {
        return array_column($facets['category'], 'count', 'slug');
    }
}
