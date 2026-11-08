<?php

declare(strict_types=1);

namespace App\Site\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use App\Site\Domain\BrandColors;

final readonly class SiteTheme
{
    public function __construct(
        #[ApiProperty(description: 'Jetons du thème clair (fond #ffffff).', required: true)]
        public ThemeTokens $light,
        #[ApiProperty(description: 'Jetons du thème sombre (fond #0f141d), couleurs éclaircies jusqu\'à 7:1 sur le fond.', required: true)]
        public ThemeTokens $dark,
    ) {
    }

    public static function fromColors(?BrandColors $colors): ?self
    {
        return null === $colors ? null : new self(ThemeTokens::fromPalette($colors->lightPalette()), ThemeTokens::fromPalette($colors->darkPalette()));
    }
}
