<?php

declare(strict_types=1);

namespace App\Media\Infrastructure\ApiPlatform;

use App\Media\Domain\Image;
use App\Media\Domain\ImageFormat;
use App\Media\Domain\ImageUsage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Construit l'image du contrat. MEDIA_BASE_URL est l'origine publique de l'API, vue par le navigateur : les adresses
 * sont absolues, puisque les fronts ne sont pas servis par l'API.
 */
final readonly class ImageViews
{
    private string $baseUrl;

    public function __construct(
        #[Autowire('%env(MEDIA_BASE_URL)%')]
        string $baseUrl,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/').'/media/';
    }

    public function view(?Image $image, ImageUsage $usage): ?ImageView
    {
        if (null === $image) {
            return null;
        }
        $widths = $usage->widths();
        $sources = [];
        foreach (ImageFormat::cases() as $format) {
            $sources[] = new ImageSource($format->mimeType(), implode(', ', array_map(
                fn (int $width): string => $this->url($image, $width, $format).' '.$width.'w',
                $widths,
            )));
        }
        $largest = $usage->largestWidth();

        return new ImageView($image->alt, $largest, $usage->heightFor($largest), $sources, $this->url($image, $widths[0], ImageFormat::Webp));
    }

    private function url(Image $image, int $width, ImageFormat $format): string
    {
        return $this->baseUrl.Image::fileName($image->key, $width, $format);
    }
}
