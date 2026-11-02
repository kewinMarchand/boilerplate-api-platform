<?php

declare(strict_types=1);

namespace App\Tests\Unit\Catalog\Domain;

use App\Catalog\Domain\CatalogRuleViolation;
use App\Catalog\Domain\Category;
use App\Catalog\Domain\Exposure;
use App\Catalog\Domain\Price;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\Size;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    #[TestWith([0])]
    #[TestWith([13])]
    public function testRefusesAnUnknownImage(int $image): void
    {
        $this->expectException(CatalogRuleViolation::class);
        new Product('p', 'P', new Category('c', 'C', 1), Price::fromCents(100), Exposure::Sun, Size::Small, true, $image);
    }

    public function testUpdateRefusesAnUnknownImage(): void
    {
        $category = new Category('c', 'C', 1);
        $product = new Product('p', 'P', $category, Price::fromCents(100), Exposure::Sun, Size::Small, true, 1);

        $this->expectException(CatalogRuleViolation::class);
        $product->update('p', 'P', $category, Price::fromCents(100), Exposure::Sun, Size::Small, true, 13);
    }

    public function testUpdatesItsFields(): void
    {
        $product = new Product('p', 'P', new Category('c', 'C', 1), Price::fromCents(100), Exposure::Sun, Size::Small, true, 1);
        $other = new Category('d', 'D', 2);

        $product->update('q', 'Q', $other, Price::fromCents(0), Exposure::Shade, Size::Large, false, 12);

        self::assertSame(
            ['q', 'Q', $other, 0, Exposure::Shade, Size::Large, false, 12],
            [$product->getSlug(), $product->getName(), $product->getCategory(), $product->getPrice()->cents, $product->getExposure(), $product->getSize(), $product->isInStock(), $product->getImage()],
        );
    }
}
