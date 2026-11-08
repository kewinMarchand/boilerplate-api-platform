<?php

declare(strict_types=1);

namespace App\Site\Domain;

/**
 * Déclinaisons d'une couleur de marque dans un thème : la couleur, sa variante -dark, le texte lisible dessus et trois
 * surfaces teintées (SPEC-COMMUNE, section 22).
 */
final readonly class ColorShades
{
    private const string BLACK = '#000000';
    private const string WHITE = '#ffffff';
    private const string DARK_TEXT = '#111827';
    private const float DARK_PART = 0.25;

    private function __construct(
        public HexColor $color,
        public HexColor $dark,
        public HexColor $on,
        public HexColor $surfaceSubtle,
        public HexColor $surface,
        public HexColor $surfaceStrong,
    ) {
    }

    /**
     * Règles des chartes des maquettes (maquettes-sites/chartes.py, declinaisons) : -dark à 25 % de noir, texte blanc
     * ou quasi-noir, surfaces à 8, 18 et 32 %.
     */
    public static function light(HexColor $color, HexColor $background): self
    {
        return new self(
            $color,
            $color->mix(new HexColor(self::BLACK), self::DARK_PART),
            self::textOn($color),
            $background->mix($color, 0.08),
            $background->mix($color, 0.18),
            $background->mix($color, 0.32),
        );
    }

    /**
     * Règles des jetons sombres de boilerplates-ui, pour une couleur déjà éclaircie : -dark à 25 % de blanc, texte de la
     * couleur du fond, surfaces à 8, 12 et 19 %.
     */
    public static function dark(HexColor $color, HexColor $background): self
    {
        return new self(
            $color,
            $color->mix(new HexColor(self::WHITE), self::DARK_PART),
            $background,
            $background->mix($color, 0.08),
            $background->mix($color, 0.12),
            $background->mix($color, 0.19),
        );
    }

    /**
     * Blanc ou quasi-noir, celui qui contraste le plus ; blanc à égalité.
     */
    private static function textOn(HexColor $color): HexColor
    {
        $white = new HexColor(self::WHITE);
        $darkText = new HexColor(self::DARK_TEXT);

        return $color->contrastWith($darkText) > $color->contrastWith($white) ? $darkText : $white;
    }
}
