<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Media\Application\ChangeImage;
use App\Media\Application\StoreImage;
use App\Media\Domain\Image;
use App\Media\Domain\ImageUsage;

final class TestImages
{
    /** Contenu accepté pour tous les usages par FakeImageEncoder. */
    public const string PHOTO = 'image/jpeg 1600x1600';

    public static function image(string $alt = 'Photo', string $key = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'): Image
    {
        return new Image($key, $alt);
    }

    /**
     * Image attendue dans le contrat pour une photo de démonstration importée par les fixtures (MEDIA_BASE_URL de .env).
     *
     * @return array{alt: string, width: int, height: int, sources: list<array{type: string, srcset: string}>, src: string}
     */
    public static function demoView(string $name, ImageUsage $usage, string $alt): array
    {
        $content = (string) file_get_contents(\dirname(__DIR__, 2).'/fixtures/images/'.$name.'.webp');
        $media = 'http://localhost:8090/media/'.substr(hash('sha256', $usage->value."\n".$content), 0, 32);
        $widths = $usage->widths();
        $small = $widths[0];
        $large = $widths[\count($widths) - 1];
        $srcset = static fn (string $format): string => implode(', ', array_map(static fn (int $width): string => \sprintf('%s-%d.%s %dw', $media, $width, $format, $width), $widths));

        return [
            'alt' => $alt,
            'width' => $large,
            'height' => $usage->heightFor($large),
            'sources' => [['type' => 'image/avif', 'srcset' => $srcset('avif')], ['type' => 'image/webp', 'srcset' => $srcset('webp')]],
            'src' => \sprintf('%s-%d.webp', $media, $small),
        ];
    }

    public static function changeImage(?InMemoryMediaStorage $storage = null): ChangeImage
    {
        return new ChangeImage(new StoreImage(new FakeImageEncoder(), $storage ?? new InMemoryMediaStorage()));
    }
}
