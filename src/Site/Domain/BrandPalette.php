<?php

declare(strict_types=1);

namespace App\Site\Domain;

/**
 * Couleurs de marque d'un thème : les douze jetons de boilerplates-ui, calculés depuis les couleurs principale et
 * secondaire (SPEC-COMMUNE, section 22). En sombre, chaque couleur est d'abord éclaircie, par pas de 5 % de blanc,
 * jusqu'à contraster à 7:1 avec le fond, comme les jetons sombres de la bibliothèque.
 */
final readonly class BrandPalette
{
    public const string LIGHT_BACKGROUND = '#ffffff';
    public const string DARK_BACKGROUND = '#0f141d';
    public const float DARK_CONTRAST = 7.0;
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

        return new self(
            ColorShades::dark(self::readableOn($primary, $background), $background),
            ColorShades::dark(self::readableOn($secondary, $background), $background),
        );
    }

    /**
     * Le blanc pur contraste à plus de 7:1 avec le fond sombre : la boucle s'arrête au plus tard au vingtième pas.
     */
    private static function readableOn(HexColor $color, HexColor $background): HexColor
    {
        $white = new HexColor(self::LIGHT_BACKGROUND);
        for ($step = 0;; ++$step) {
            $lightened = $color->mix($white, $step * self::LIGHTENING_STEP);
            if ($lightened->contrastWith($background) >= self::DARK_CONTRAST) {
                return $lightened;
            }
        }
    }
}
