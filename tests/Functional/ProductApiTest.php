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

    public function testListsFirstPageWithTotalsAndFacetsWithoutFilter(): void
    {
        $body = $this->products('');

        self::assertCount(12, $body['items']);
        self::assertSame(['totalItems' => 24, 'page' => 1, 'itemsPerPage' => 12, 'totalPages' => 2], self::pagination($body));
        self::assertSame(['id' => 1, 'slug' => 'monstera-deliciosa', 'name' => 'Monstera deliciosa', 'categorySlug' => 'monstera', 'price' => 3490, 'exposure' => 'mi-ombre', 'size' => 'M', 'inStock' => true, 'image' => TestImages::demoView('product-1', ImageUsage::Product, 'Monstera deliciosa')], $body['items'][0]);
        self::assertSame(['soleil' => 14, 'mi-ombre' => 7, 'ombre' => 3], self::counts($body, 'exposure'));
        self::assertSame(['S' => 6, 'M' => 9, 'L' => 9], self::counts($body, 'size'));
        self::assertSame(['plantes-interieur' => 12, 'plantes-exterieur' => 9, 'plantes-aquatiques' => 3], self::categoryCounts($body));
    }

    public function testCategoryFilterIncludesTheWholeBranch(): void
    {
        $body = $this->products('category=plantes-interieur');

        self::assertSame(12, $body['totalItems']);
        self::assertSame(['feuillages' => 6, 'plantes-a-fleurs' => 6], self::categoryCounts($body));
    }

    public function testCategoryFacetListsDirectChildrenOfTheBranch(): void
    {
        $body = $this->products('category=feuillages');

        self::assertSame(6, $body['totalItems']);
        self::assertSame(['monstera' => 3, 'fougeres' => 3], self::categoryCounts($body));
        self::assertSame(['soleil' => 0, 'mi-ombre' => 4, 'ombre' => 2], self::counts($body, 'exposure'));
    }

    public function testLeafCategoryHasNoCategoryFacet(): void
    {
        $body = $this->products('category=monstera');

        self::assertSame(3, $body['totalItems']);
        self::assertSame([], $body['facets']['category']);
    }

    public function testUnknownCategoryReturnsNotFound(): void
    {
        static::createClient()->request('GET', '/api/products?category=cactus', ['headers' => ['Accept' => 'application/json']]);

        self::assertResponseStatusCodeSame(404);
        self::assertJsonContains(['detail' => 'Catégorie introuvable : cactus']);
    }

    public function testExposureFilterKeepsItsOwnFacetCountsDisjunctive(): void
    {
        $body = $this->products('exposure[]=ombre');

        self::assertSame(3, $body['totalItems']);
        self::assertSame(['soleil' => 14, 'mi-ombre' => 7, 'ombre' => 3], self::counts($body, 'exposure'));
        self::assertSame(['S' => 2, 'M' => 1, 'L' => 0], self::counts($body, 'size'));
        self::assertSame(['plantes-interieur' => 3, 'plantes-exterieur' => 0, 'plantes-aquatiques' => 0], self::categoryCounts($body));
    }

    public function testValuesOfOneFacetAreCombinedWithOr(): void
    {
        self::assertSame(10, $this->products('exposure[]=ombre&exposure[]=mi-ombre')['totalItems']);
    }

    public function testSizeFilterKeepsItsOwnFacetCountsDisjunctive(): void
    {
        $body = $this->products('size[]=L');

        self::assertSame(9, $body['totalItems']);
        self::assertSame(['S' => 6, 'M' => 9, 'L' => 9], self::counts($body, 'size'));
        self::assertSame(['soleil' => 6, 'mi-ombre' => 3, 'ombre' => 0], self::counts($body, 'exposure'));
    }

    public function testCombinedFacetsCountEachOneAgainstTheOthers(): void
    {
        $body = $this->products('exposure[]=soleil&size[]=S');

        self::assertSame(3, $body['totalItems']);
        self::assertSame(['soleil' => 3, 'mi-ombre' => 1, 'ombre' => 2], self::counts($body, 'exposure'));
        self::assertSame(['S' => 3, 'M' => 5, 'L' => 6], self::counts($body, 'size'));
    }

    public function testPriceRangeIsInclusiveAndInCents(): void
    {
        $body = $this->products('priceMin=2290&priceMax=2990');

        self::assertSame(5, $body['totalItems']);
        self::assertSame([2490, 2690, 2990, 2290, 2490], array_column($body['items'], 'price'));
    }

    public function testInStockFilter(): void
    {
        $body = $this->products('inStock=true');

        self::assertSame(19, $body['totalItems']);
        self::assertNotContains(false, array_column($this->products('inStock=true&itemsPerPage=48')['items'], 'inStock'));
    }

    public function testSortsByPrice(): void
    {
        self::assertSame([1290, 1490], \array_slice(array_column($this->products('order[price]=asc')['items'], 'price'), 0, 2));
        self::assertSame([12900, 8990], \array_slice(array_column($this->products('order[price]=desc')['items'], 'price'), 0, 2));
    }

    public function testSortsByNameWithFrenchCollation(): void
    {
        self::assertSame('anthurium-andreanum-rouge', $this->products('order[name]=asc')['items'][0]['slug']);
        self::assertSame('strelitzia-reginae', $this->products('order[name]=desc')['items'][0]['slug']);
    }

    public function testPaginates(): void
    {
        $second = $this->products('page=2');
        self::assertSame(13, $second['items'][0]['id']);
        self::assertCount(12, $second['items']);

        $custom = $this->products('itemsPerPage=5&page=5');
        self::assertSame(['totalItems' => 24, 'page' => 5, 'itemsPerPage' => 5, 'totalPages' => 5], self::pagination($custom));
        self::assertCount(4, $custom['items']);

        self::assertSame([], $this->products('page=3')['items']);
    }

    public function testIgnoresInvalidValues(): void
    {
        $body = $this->products('exposure[]=lune&size[]=XL&priceMin=abc&inStock=peut-etre&order[price]=haut&page=0&itemsPerPage=500');

        self::assertSame(['totalItems' => 24, 'page' => 1, 'itemsPerPage' => 12, 'totalPages' => 2], self::pagination($body));
        self::assertSame(1, $body['items'][0]['id']);

        $huge = $this->products('page=99999999999999999999');
        self::assertSame([], $huge['items']);
        self::assertSame(24, $huge['totalItems']);
    }

    /**
     * @return array{items: list<array<string, mixed>>, totalItems: int, page: int, itemsPerPage: int, totalPages: int, facets: array{exposure: list<array{value: string, count: int}>, size: list<array{value: string, count: int}>, category: list<array{slug: string, name: string, count: int}>}}
     */
    private function products(string $query): array
    {
        $response = static::createClient()->request('GET', '/api/products?'.$query, ['headers' => ['Accept' => 'application/json']]);
        self::assertResponseIsSuccessful();

        /** @var array{items: list<array<string, mixed>>, totalItems: int, page: int, itemsPerPage: int, totalPages: int, facets: array{exposure: list<array{value: string, count: int}>, size: list<array{value: string, count: int}>, category: list<array{slug: string, name: string, count: int}>}} $body */
        $body = $response->toArray();

        return $body;
    }

    /**
     * @param array{totalItems: int, page: int, itemsPerPage: int, totalPages: int} $body
     *
     * @return array{totalItems: int, page: int, itemsPerPage: int, totalPages: int}
     */
    private static function pagination(array $body): array
    {
        return ['totalItems' => $body['totalItems'], 'page' => $body['page'], 'itemsPerPage' => $body['itemsPerPage'], 'totalPages' => $body['totalPages']];
    }

    /**
     * @param array{facets: array{exposure: list<array{value: string, count: int}>, size: list<array{value: string, count: int}>}} $body
     * @param 'exposure'|'size'                                                                                                    $facet
     *
     * @return array<string, int>
     */
    private static function counts(array $body, string $facet): array
    {
        return array_column($body['facets'][$facet], 'count', 'value');
    }

    /**
     * @param array{facets: array{category: list<array{slug: string, name: string, count: int}>}} $body
     *
     * @return array<string, int>
     */
    private static function categoryCounts(array $body): array
    {
        return array_column($body['facets']['category'], 'count', 'slug');
    }
}
