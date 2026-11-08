<?php

declare(strict_types=1);

namespace App\Site\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use App\Site\Domain\BrandPalette;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Les douze jetons de couleur de marque de boilerplates-ui pour un thème, sous leur nom sans le préfixe « color. ».
 */
final readonly class ThemeTokens
{
    private const array HEX = ['type' => 'string', 'pattern' => '^#[0-9a-f]{6}$'];

    public function __construct(
        #[ApiProperty(required: true, schema: self::HEX)]
        public string $primary,
        #[ApiProperty(required: true, schema: self::HEX), SerializedName('primary-dark')]
        public string $primaryDark,
        #[ApiProperty(required: true, schema: self::HEX), SerializedName('on-primary')]
        public string $onPrimary,
        #[ApiProperty(required: true, schema: self::HEX), SerializedName('primary-surface-subtle')]
        public string $primarySurfaceSubtle,
        #[ApiProperty(required: true, schema: self::HEX), SerializedName('primary-surface')]
        public string $primarySurface,
        #[ApiProperty(required: true, schema: self::HEX), SerializedName('primary-surface-strong')]
        public string $primarySurfaceStrong,
        #[ApiProperty(required: true, schema: self::HEX)]
        public string $secondary,
        #[ApiProperty(required: true, schema: self::HEX), SerializedName('secondary-dark')]
        public string $secondaryDark,
        #[ApiProperty(required: true, schema: self::HEX), SerializedName('on-secondary')]
        public string $onSecondary,
        #[ApiProperty(required: true, schema: self::HEX), SerializedName('secondary-surface-subtle')]
        public string $secondarySurfaceSubtle,
        #[ApiProperty(required: true, schema: self::HEX), SerializedName('secondary-surface')]
        public string $secondarySurface,
        #[ApiProperty(required: true, schema: self::HEX), SerializedName('secondary-surface-strong')]
        public string $secondarySurfaceStrong,
    ) {
    }

    public static function fromPalette(BrandPalette $palette): self
    {
        [$primary, $secondary] = [$palette->primary, $palette->secondary];

        return new self(
            $primary->color->value,
            $primary->dark->value,
            $primary->on->value,
            $primary->surfaceSubtle->value,
            $primary->surface->value,
            $primary->surfaceStrong->value,
            $secondary->color->value,
            $secondary->dark->value,
            $secondary->on->value,
            $secondary->surfaceSubtle->value,
            $secondary->surface->value,
            $secondary->surfaceStrong->value,
        );
    }
}
