<?php

declare(strict_types=1);

namespace App\Tests\Unit\Catalog\Application;

use App\Catalog\Application\CreateProduct;
use App\Catalog\Application\DeleteProduct;
use App\Catalog\Application\ProductData;
use App\Catalog\Application\UpdateProduct;
use App\Catalog\Domain\CatalogRuleViolation;
use App\Catalog\Domain\Category;
use App\Catalog\Domain\Exposure;
use App\Catalog\Domain\Price;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\Size;
use App\Tests\Support\InMemoryCategoryRepository;
use App\Tests\Support\InMemoryProductRepository;
use PHPUnit\Framework\TestCase;

final class ProductWritesTest extends TestCase
{
    private InMemoryCategoryRepository $categories;
    private InMemoryProductRepository $products;
    private Category $palms;
    private Product $dwarfPalm;

    protected function setUp(): void
    {
        $this->palms = new Category('palmiers', 'Palmiers', 1);
        $this->categories = new InMemoryCategoryRepository([$this->palms]);
        $this->dwarfPalm = new Product('palmier-nain', 'Palmier nain', $this->palms, Price::fromCents(4490), Exposure::Sun, Size::Medium, true, 1);
        $this->products = new InMemoryProductRepository([$this->dwarfPalm]);
    }

    public function testCreatesAProduct(): void
    {
        $created = new CreateProduct($this->products, $this->categories)($this->data('palmier-geant', $this->palms->getId(), 9900));

        self::assertSame(9900, $created->getPrice()->cents);
        self::assertSame($created, $this->products->findBySlug('palmier-geant'));
    }

    public function testRefusesATakenSlug(): void
    {
        $this->expectException(CatalogRuleViolation::class);
        new CreateProduct($this->products, $this->categories)($this->data('palmier-nain', $this->palms->getId(), 100));
    }

    public function testRefusesAMissingCategory(): void
    {
        $this->expectExceptionObject(new CatalogRuleViolation('La catégorie du produit n\'existe pas.'));
        new CreateProduct($this->products, $this->categories)($this->data('sans-categorie', null, 100));
    }

    public function testRefusesANegativePrice(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CreateProduct($this->products, $this->categories)($this->data('negatif', $this->palms->getId(), -1));
    }

    public function testUpdatesAProductAndKeepsItsSlug(): void
    {
        new UpdateProduct($this->products, $this->categories)((int) $this->dwarfPalm->getId(), $this->data('palmier-nain', $this->palms->getId(), 0));

        self::assertSame(0, $this->dwarfPalm->getPrice()->cents);
    }

    public function testDeletesAProduct(): void
    {
        new DeleteProduct($this->products)((int) $this->dwarfPalm->getId());

        self::assertNull($this->products->findBySlug('palmier-nain'));
    }

    private function data(string $slug, ?int $categoryId, int $price): ProductData
    {
        $data = new ProductData();
        $data->slug = $slug;
        $data->name = ucfirst(str_replace('-', ' ', $slug));
        $data->categoryId = $categoryId;
        $data->price = $price;

        return $data;
    }
}
