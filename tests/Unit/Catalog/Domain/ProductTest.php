<?php

declare(strict_types=1);

namespace App\Tests\Unit\Catalog\Domain;

use App\Catalog\Domain\Category;
use App\Catalog\Domain\Exposure;
use App\Catalog\Domain\Price;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\Size;
use App\Tests\Support\TestImages;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    public function testUpdatesItsFields(): void
    {
        $product = new Product('p', 'P', new Category('c', 'C', 1), Price::fromCents(100), Exposure::Sun, Size::Small, true, TestImages::image());
        $other = new Category('d', 'D', 2);

        $image = TestImages::image('Q', str_repeat('c', 32));
        $product->update('q', 'Q', $other, Price::fromCents(0), Exposure::Shade, Size::Large, false, $image);

        self::assertEquals(
            ['q', 'Q', $other, 0, Exposure::Shade, Size::Large, false, $image],
            [$product->getSlug(), $product->getName(), $product->getCategory(), $product->getPrice()->cents, $product->getExposure(), $product->getSize(), $product->isInStock(), $product->getImage()],
        );
    }
}
