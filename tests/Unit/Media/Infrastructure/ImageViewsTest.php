<?php

declare(strict_types=1);

namespace App\Tests\Unit\Media\Infrastructure;

use App\Media\Domain\ImageUsage;
use App\Media\Infrastructure\ApiPlatform\ImageSource;
use App\Media\Infrastructure\ApiPlatform\ImageView;
use App\Media\Infrastructure\ApiPlatform\ImageViews;
use App\Tests\Support\TestImages;
use PHPUnit\Framework\TestCase;

final class ImageViewsTest extends TestCase
{
    public function testBuildsTheContractImageWithAbsoluteUrls(): void
    {
        $key = str_repeat('a', 32);
        $view = new ImageViews('https://api.exemple.fr/')->view(TestImages::image('Monstera', $key), ImageUsage::Product);

        $media = 'https://api.exemple.fr/media/'.$key;
        self::assertEquals(new ImageView(
            'Monstera',
            800,
            800,
            [
                new ImageSource('image/avif', $media.'-400.avif 400w, '.$media.'-640.avif 640w, '.$media.'-800.avif 800w'),
                new ImageSource('image/webp', $media.'-400.webp 400w, '.$media.'-640.webp 640w, '.$media.'-800.webp 800w'),
            ],
            $media.'-400.webp',
        ), $view);
    }

    public function testSizesFollowTheRatioOfTheUsage(): void
    {
        $view = new ImageViews('http://localhost:8090')->view(TestImages::image(), ImageUsage::Article);

        self::assertSame([960, 540], [$view?->width, $view?->height]);
    }

    public function testNoImageGivesNull(): void
    {
        self::assertNull(new ImageViews('http://localhost:8090')->view(null, ImageUsage::Article));
    }
}
