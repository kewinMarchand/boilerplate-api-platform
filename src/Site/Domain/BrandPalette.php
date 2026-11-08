<?php

declare(strict_types=1);

namespace App\Site\Domain;

/**
 * Couleurs de marque d'un thème : les douze jetons de boilerplates-ui, calculés depuis les couleurs principale et
 * secondaire (SPEC-COMMUNE, section 22). En sombre, chaque couleur est d'abord éclaircie, par pas de 5 % de blanc,
 * jusqu'à contraster à 4,5:1 avec la surface secondaire sombre, plus claire que le fond : le minimum AA du texte courant,
 * sur le fond comme sur les surfaces, qui garde la couleur reconnaissable. Les surfaces restent mélangées avec le fond.
 */
final readonly class BrandPalette
{
    public const string LIGHT_BACKGROUND = '#ffffff';
    public const string DARK_BACKGROUND = '#0f141d';
    public const string DARK_CONTRAST_SURFACE = '#1c222e';
    public const float DARK_CONTRAST = 4.5;
    private const float LIGHTENING_STEP = 0.05;

    private function __construct(
        public ColorShades $primary,
        public ColorShades $secondary,
    ) {
    }

    public static function light(HexColor $primary, HexColor $secondary): self
    {
        $background = new HexColor(self::LIGHT_BACKGROUND);

        return new self(ColorShades::light($primary, $background), ColorShades::light($secondary, $background));
    }

    public static function dark(HexColor $primary, HexColor $secondary): self
    {
        $background = new HexColor(self::DARK_BACKGROUND);
        $surface = new HexColor(self::DARK_CONTRAST_SURFACE);

        return new self(
            ColorShades::dark(self::readableOn($primary, $surface), $background),
            ColorShades::dark(self::readableOn($secondary, $surface), $background),
        );
    }

    /**
     * Le blanc pur contraste à plus de 4,5:1 avec la surface sombre : la boucle s'arrête au plus tard au vingtième pas.
     */
    private static function readableOn(HexColor $color, HexColor $surface): HexColor
    {
        $white = new HexColor(self::LIGHT_BACKGROUND);
        for ($step = 0;; ++$step) {
            $lightened = $color->mix($white, $step * self::LIGHTENING_STEP);
            if ($lightened->contrastWith($surface) >= self::DARK_CONTRAST) {
                return $lightened;
            }
        }
    }
}
