<?php

declare(strict_types=1);

namespace App\Tests\Unit\Media\Infrastructure;

use App\Media\Domain\Audio;
use App\Media\Domain\AudioFormat;
use App\Media\Domain\Image;
use App\Media\Domain\ImageUsage;
use App\Media\Infrastructure\ApiPlatform\AudioView;
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

        self::assertSame([960, 540], [$view->width, $view->height]);
    }

    public function testANarrowProductSourceStopsAtItsWidth(): void
    {
        $view = new ImageViews('http://localhost:8090')->view(new Image(str_repeat('a', 32), 'Fleur', null, 600), ImageUsage::Product);
        $media = 'http://localhost:8090/media/'.str_repeat('a', 32);

        self::assertSame([600, 600], [$view->width, $view->height]);
        self::assertSame($media.'-400.webp 400w, '.$media.'-600.webp 600w', $view->sources[1]->srcset);
    }

    public function testAnAudioFileIsServedUnderMedia(): void
    {
        $view = new ImageViews('http://localhost:8090/')->audio(new Audio(str_repeat('b', 32), AudioFormat::M4a, 1560));

        self::assertEquals(new AudioView('http://localhost:8090/media/'.str_repeat('b', 32).'.m4a', 'audio/mp4', 1560), $view);
    }

    public function testNoImageGivesNull(): void
    {
        /** @var Image|null $none */
        $none = null;

        self::assertNull(new ImageViews('http://localhost:8090')->view($none, ImageUsage::Article));
    }
}
