<?php

declare(strict_types=1);

namespace App\Media\Infrastructure\Gd;

use App\Media\Domain\Image;
use App\Media\Domain\ImageEncoder;
use App\Media\Domain\ImageFormat;
use App\Media\Domain\ImageUsage;
use App\Media\Domain\MediaRuleViolation;
use App\Media\Domain\SourceImage;

/**
 * Encodeur GD (extensions gd, avec AVIF et WebP, et exif). GD réencode chaque pixel : aucune métadonnée du fichier
 * envoyé (EXIF, GPS, profil) ne passe dans les variantes.
 */
final readonly class GdImageEncoder implements ImageEncoder
{
    private const int AVIF_QUALITY = 50;
    private const int AVIF_SPEED = 6;
    private const int WEBP_QUALITY = 75;

    public function inspect(string $content): ?SourceImage
    {
        $size = @getimagesizefromstring($content);
        if (false === $size || $size[0] < 1 || $size[1] < 1) {
            return null;
        }
        $mimeType = $size['mime'];
        [$width, $height] = [$size[0], $size[1]];
        if ('image/jpeg' === $mimeType && \in_array($this->orientation($content), [5, 6, 7, 8], true)) {
            [$width, $height] = [$height, $width];
        }

        return new SourceImage($mimeType, $width, $height);
    }

    public function variants(string $content, string $key, ImageUsage $usage): iterable
    {
        $source = $this->load($content);
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        [$ratioWidth, $ratioHeight] = $usage->ratio() ?? [$sourceWidth, $sourceHeight];
        $cropWidth = min($sourceWidth, intdiv($sourceHeight * $ratioWidth, $ratioHeight));
        $cropHeight = min($sourceHeight, intdiv($cropWidth * $ratioHeight, $ratioWidth));
        $cropX = intdiv($sourceWidth - $cropWidth, 2);
        $cropY = intdiv($sourceHeight - $cropHeight, 2);

        foreach ($usage->widths() as $width) {
            $height = $usage->heightFor($width, [$sourceWidth, $sourceHeight]);
            $variant = imagecreatetruecolor($width, $height);
            if (false === $variant) {
                throw new \RuntimeException('GD n\'a pas pu créer la variante.');
            }
            imagealphablending($variant, false);
            imagesavealpha($variant, true);
            imagecopyresampled($variant, $source, 0, 0, $cropX, $cropY, $width, $height, $cropWidth, $cropHeight);
            foreach (ImageFormat::cases() as $format) {
                yield Image::fileName($key, $width, $format) => $this->encode($variant, $format);
            }
        }
    }

    private function load(string $content): \GdImage
    {
        $image = @imagecreatefromstring($content);
        if (false === $image) {
            $image = $this->loadAvif($content);
        }
        if (!imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        return match ($this->orientation($content)) {
            2 => $this->flip($image, \IMG_FLIP_HORIZONTAL),
            3 => $this->rotate($image, 180),
            4 => $this->flip($image, \IMG_FLIP_VERTICAL),
            5 => $this->flip($this->rotate($image, 270), \IMG_FLIP_HORIZONTAL),
            6 => $this->rotate($image, 270),
            7 => $this->flip($this->rotate($image, 90), \IMG_FLIP_HORIZONTAL),
            8 => $this->rotate($image, 90),
            default => $image,
        };
    }

    /**
     * imagecreatefromstring ne reconnaît pas l'AVIF : passage par un fichier temporaire.
     */
    private function loadAvif(string $content): \GdImage
    {
        $path = tempnam(sys_get_temp_dir(), 'avif');
        if (false === $path) {
            throw new \RuntimeException('Fichier temporaire impossible à créer.');
        }
        try {
            file_put_contents($path, $content);
            $image = @imagecreatefromavif($path);
        } finally {
            unlink($path);
        }
        if (false === $image) {
            throw new MediaRuleViolation('Le fichier ne se lit pas comme une image.');
        }

        return $image;
    }

    private function orientation(string $content): int
    {
        if (!str_starts_with($content, "\xFF\xD8")) {
            return 1;
        }
        $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($content));
        $orientation = \is_array($exif) ? ($exif['Orientation'] ?? 1) : 1;

        return \is_int($orientation) ? $orientation : 1;
    }

    private function rotate(\GdImage $image, int $degrees): \GdImage
    {
        $rotated = imagerotate($image, $degrees, 0);
        if (false === $rotated) {
            throw new \RuntimeException('GD n\'a pas pu tourner l\'image.');
        }

        return $rotated;
    }

    private function flip(\GdImage $image, int $mode): \GdImage
    {
        imageflip($image, $mode);

        return $image;
    }

    private function encode(\GdImage $image, ImageFormat $format): string
    {
        ob_start();
        $written = match ($format) {
            ImageFormat::Avif => imageavif($image, null, self::AVIF_QUALITY, self::AVIF_SPEED),
            ImageFormat::Webp => imagewebp($image, null, self::WEBP_QUALITY),
        };
        $bytes = (string) ob_get_clean();
        if (!$written || '' === $bytes) {
            throw new \RuntimeException(\sprintf('GD n\'a pas pu encoder en %s.', $format->value));
        }

        return $bytes;
    }
}
