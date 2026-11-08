<?php

declare(strict_types=1);

namespace App\Tests\Unit\Media\Domain;

use App\Media\Domain\ImageFormat;
use App\Media\Domain\ImageUsage;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class ImageUsageTest extends TestCase
{
    /**
     * @param positive-int       $sourceWidth
     * @param list<positive-int> $expected
     */
    #[TestWith([400, [400]])]
    #[TestWith([600, [400, 600]])]
    #[TestWith([640, [400, 640]])]
    #[TestWith([700, [400, 640, 700]])]
    #[TestWith([800, [400, 640, 800]])]
    #[TestWith([2000, [400, 640, 800]])]
    public function testProductWidthsStopAtTheSourceWidth(int $sourceWidth, array $expected): void
    {
        self::assertSame($expected, ImageUsage::Product->widthsUpTo($sourceWidth));
    }

    public function testOnlyProductsAcceptASourceNarrowerThanTheirLargestVariant(): void
    {
        self::assertSame([400, 960, 400, 1200, 512], [
            ImageUsage::Product->minimumWidth(),
            ImageUsage::Article->minimumWidth(),
            ImageUsage::Logo->minimumWidth(),
            ImageUsage::Share->minimumWidth(),
            ImageUsage::Icon->minimumWidth(),
        ]);
    }

    public function testTheShareImageIsTwelveHundredBySixHundredThirty(): void
    {
        self::assertSame(630, ImageUsage::Share->heightFor(1200));
    }

    public function testIconsArePngWhilePhotosAreAvifThenWebp(): void
    {
        self::assertSame([[ImageFormat::Png], ImageFormat::Png], [ImageUsage::Icon->formats(), ImageUsage::Icon->fallbackFormat()]);
        self::assertSame([[ImageFormat::Avif, ImageFormat::Webp], ImageFormat::Webp], [ImageUsage::Product->formats(), ImageUsage::Product->fallbackFormat()]);
    }
}
