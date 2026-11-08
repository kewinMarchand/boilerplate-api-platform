<?php

declare(strict_types=1);

namespace App\Site\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use App\Media\Domain\Image;
use App\Media\Domain\ImageFormat;
use App\Media\Infrastructure\ApiPlatform\ImageViews;

/**
 * Favicons générés depuis l'icône du site (ADR 0012) : adresses absolues des PNG carrés.
 */
final readonly class SiteIcon
{
    public function __construct(
        #[ApiProperty(description: 'PNG de 32 px : <link rel="icon" sizes="32x32">.', required: true)]
        public string $png32,
        #[ApiProperty(description: 'PNG de 180 px : <link rel="apple-touch-icon">.', required: true)]
        public string $png180,
        #[ApiProperty(description: 'PNG de 192 px : icône du manifeste.', required: true)]
        public string $png192,
        #[ApiProperty(description: 'PNG de 512 px : icône du manifeste.', required: true)]
        public string $png512,
    ) {
    }

    /**
     * @return ($icon is null ? null : self)
     */
    public static function fromModel(?Image $icon, ImageViews $images): ?self
    {
        if (null === $icon) {
            return null;
        }
        $url = static fn (int $width): string => $images->url($icon, $width, ImageFormat::Png);

        return new self($url(32), $url(180), $url(192), $url(512));
    }
}
