<?php

declare(strict_types=1);

namespace App\Media\Infrastructure\ApiPlatform;

use App\Media\Domain\Audio;
use App\Media\Domain\Image;
use App\Media\Domain\ImageFormat;
use App\Media\Domain\ImageUsage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Construit l'image et le fichier audio du contrat. MEDIA_BASE_URL est l'origine publique de l'API, vue par le navigateur : les adresses
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

    /**
     * @return ($image is null ? null : ImageView)
     */
    public function view(?Image $image, ImageUsage $usage): ?ImageView
    {
        if (null === $image) {
            return null;
        }
        $widths = $usage->widthsUpTo(max(1, $image->width ?? $usage->largestWidth()));
        $sources = [];
        foreach ($usage->formats() as $format) {
            $sources[] = new ImageSource($format->mimeType(), implode(', ', array_map(
                fn (int $width): string => $this->url($image, $width, $format).' '.$width.'w',
                $widths,
            )));
        }
        $largest = $widths[\count($widths) - 1];

        return new ImageView($image->alt, $largest, $image->height ?? $usage->heightFor($largest), $sources, $this->url($image, $widths[0], $usage->fallbackFormat()));
    }

    /**
     * @return ($audio is null ? null : AudioView)
     */
    public function audio(?Audio $audio): ?AudioView
    {
        return null === $audio ? null : new AudioView($this->baseUrl.$audio->fileName(), $audio->format->mimeType(), $audio->durationSeconds);
    }

    public function url(Image $image, int $width, ImageFormat $format): string
    {
        return $this->baseUrl.Image::fileName($image->key, $width, $format);
    }
}
