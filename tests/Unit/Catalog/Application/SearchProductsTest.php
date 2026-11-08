<?php

declare(strict_types=1);

namespace App\Tests\Unit\Catalog\Application;

use App\Catalog\Application\SearchProducts;
use App\Catalog\Domain\AttributeChoice;
use App\Catalog\Domain\AttributeType;
use App\Catalog\Domain\Category;
use App\Catalog\Domain\CategoryNotFound;
use App\Catalog\Domain\Exposure;
use App\Catalog\Domain\Price;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductAttribute;
use App\Catalog\Domain\ProductCriteria;
use App\Catalog\Domain\ProductSearch;
use App\Catalog\Domain\Size;
use App\Tests\Support\InMemoryCategoryRepository;
use App\Tests\Support\InMemoryProductAttributeRepository;
use App\Tests\Support\InMemoryProductRepository;
use App\Tests\Support\TestImages;
use PHPUnit\Framework\TestCase;

final class SearchProductsTest extends TestCase
{
    private SearchProducts $searchProducts;

    protected function setUp(): void
    {
        $palms = new Category('palmiers', 'Palmiers', 1);
        $ferns = new Category('fougeres', 'Fougères', 2);
        $products = [
            new Product('palmier-nain', 'Palmier nain', $palms, Price::fromCents(4490), Exposure::Sun, Size::Medium, false, TestImages::image()),
            new Product('fougere-de-boston', 'Fougère de Boston', $ferns, Price::fromCents(1590), Exposure::Shade, Size::Medium, true, TestImages::image()),
            new Product('palmier-de-chine', 'Palmier de Chine', $palms, Price::fromCents(7990), Exposure::Sun, Size::Large, true, TestImages::image()),
        ];
        $products[0]->changeAttributeValues(['feuillage' => 'persistant', 'port' => 'buissonnant', 'hauteur' => '250']);
        $products[1]->changeAttributeValues(['feuillage' => 'persistant']);
        $products[2]->changeAttributeValues(['feuillage' => 'caduc', 'port' => 'buissonnant']);
        $attributes = [
            new ProductAttribute('feuillage', 'Feuillage', AttributeType::Choice, null, true, 1, [new AttributeChoice('persistant', 'Persistant'), new AttributeChoice('caduc', 'Caduc')]),
            new ProductAttribute('port', 'Port', AttributeType::Choice, null, true, 2, [new AttributeChoice('buissonnant', 'Buissonnant'), new AttributeChoice('retombant', 'Retombant')]),
            new ProductAttribute('couleur', 'Couleur', AttributeType::Choice, null, false, 3, [new AttributeChoice('vert', 'Vert')]),
            new ProductAttribute('hauteur', 'Hauteur', AttributeType::Number, 'cm', false, 4),
        ];

        $this->searchProducts = new SearchProducts(
            new InMemoryProductRepository($products),
            new InMemoryCategoryRepository([$palms, $ferns]),
            new InMemoryProductAttributeRepository($attributes),
            new ProductSearch(),
        );
    }

    public function testSearchesTheCatalogLoadedThroughThePorts(): void
    {
        $result = ($this->searchProducts)(new ProductCriteria(categorySlug: 'fougeres'));

        self::assertSame(1, $result->totalItems);
        self::assertSame('fougere-de-boston', $result->items[0]->getSlug());
    }

    public function testUnknownCategoryIsReportedByTheDomain(): void
    {
        $this->expectException(CategoryNotFound::class);

        ($this->searchProducts)(new ProductCriteria(categorySlug: 'cactus'));
    }

    public function testAttributeValuesAreCombinedWithOrAndAttributesWithAnd(): void
    {
        $either = ($this->searchProducts)(new ProductCriteria(attributes: ['feuillage' => ['persistant', 'caduc']]));
        $both = ($this->searchProducts)(new ProductCriteria(attributes: ['feuillage' => ['persistant'], 'port' => ['buissonnant']]));

        self::assertSame(3, $either->totalItems);
        self::assertSame(['palmier-nain'], array_map(static fn (Product $product): string => $product->getSlug(), $both->items));
    }

    public function testAttributeCountsIgnoreTheirOwnFilterOnly(): void
    {
        $result = ($this->searchProducts)(new ProductCriteria(attributes: ['feuillage' => ['persistant'], 'port' => ['buissonnant']]));

        self::assertSame(['feuillage' => ['persistant' => 1, 'caduc' => 1], 'port' => ['buissonnant' => 1, 'retombant' => 0]], self::attributeCounts($result->attributeCounts));
        self::assertSame(['soleil' => 1, 'mi-ombre' => 0, 'ombre' => 0], $result->exposureCounts);
    }

    public function testIgnoresUnknownCodesUnknownValuesAndAttributesThatAreNotFacets(): void
    {
        $result = ($this->searchProducts)(new ProductCriteria(attributes: ['inconnu' => ['x'], 'feuillage' => ['epineux'], 'couleur' => ['vert'], 'hauteur' => ['250']]));

        self::assertSame(3, $result->totalItems);
        self::assertSame(['feuillage', 'port'], array_keys(self::attributeCounts($result->attributeCounts)));
    }

    public function testOmitsAnAttributeFacetNoProductOfTheBranchCarries(): void
    {
        $result = ($this->searchProducts)(new ProductCriteria(categorySlug: 'fougeres'));

        self::assertSame(['feuillage' => ['persistant' => 1, 'caduc' => 0]], self::attributeCounts($result->attributeCounts));
    }

    /**
     * @param list<array{attribute: ProductAttribute, counts: array<string, int>}> $entries
     *
     * @return array<string, array<string, int>>
     */
    private static function attributeCounts(array $entries): array
    {
        $counts = [];
        foreach ($entries as $entry) {
            $counts[$entry['attribute']->getCode()] = $entry['counts'];
        }

        return $counts;
    }
}
