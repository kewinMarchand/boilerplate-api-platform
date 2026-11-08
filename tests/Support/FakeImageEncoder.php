<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Media\Domain\Image;
use App\Media\Domain\ImageEncoder;
use App\Media\Domain\ImageUsage;
use App\Media\Domain\SourceImage;

/**
 * Encodeur sans GD : le contenu « image/<type> <largeur>x<hauteur> » décrit l'image. Compte les encodages.
 */
final class FakeImageEncoder implements ImageEncoder
{
    public int $encodings = 0;

    public function inspect(string $content): ?SourceImage
    {
        if (1 !== preg_match('#^(image/[a-z]+) (\d+)x(\d+)#', $content, $match)) {
            return null;
        }

        return new SourceImage($match[1], (int) $match[2], (int) $match[3]);
    }

    public function variants(string $content, string $key, ImageUsage $usage, array $widths): iterable
    {
        ++$this->encodings;
        foreach ($widths as $width) {
            foreach ($usage->formats() as $format) {
                yield Image::fileName($key, $width, $format) => $format->value;
            }
        }
    }
}
