<?php

declare(strict_types=1);

namespace App\Site\Domain;

/**
 * Les deux couleurs de marque saisies. La principale sert de couleur de lien : elle doit contraster à 4,5:1 avec le fond
 * clair. La secondaire doit porter un texte lisible (on-secondary à 4,5:1).
 */
final readonly class BrandColors
{
    public const float MIN_CONTRAST = 4.5;

    /**
     * @throws SiteRuleViolation couleur illisible
     */
    public function __construct(
        public HexColor $primary,
        public HexColor $secondary,
    ) {
        $onLight = $primary->contrastWith(new HexColor(BrandPalette::LIGHT_BACKGROUND));
        if ($onLight < self::MIN_CONTRAST) {
            throw new SiteRuleViolation('La couleur principale doit contraster à 4,5:1 au moins avec le fond clair (actuellement %ratio%:1).', ['%ratio%' => self::ratio($onLight)]);
        }
        $text = BrandPalette::light($primary, $secondary)->secondary->on->contrastWith($secondary);
        if ($text < self::MIN_CONTRAST) {
            throw new SiteRuleViolation('La couleur secondaire ne permet pas de texte lisible dessus (au mieux %ratio%:1, 4,5:1 au minimum).', ['%ratio%' => self::ratio($text)]);
        }
    }

    /**
     * Saisie de l'admin : les deux couleurs ou aucune (null : le front garde les jetons de boilerplates-ui).
     *
     * @throws SiteRuleViolation une seule couleur, couleur invalide ou illisible
     */
    public static function fromInput(string $primary, string $secondary): ?self
    {
        $primary = trim($primary);
        $secondary = trim($secondary);
        if ('' === $primary && '' === $secondary) {
            return null;
        }
        if ('' === $primary || '' === $secondary) {
            throw new SiteRuleViolation('Saisir les deux couleurs, ou aucune.');
        }

        return new self(new HexColor($primary), new HexColor($secondary));
    }

    public function lightPalette(): BrandPalette
    {
        return BrandPalette::light($this->primary, $this->secondary);
    }

    public function darkPalette(): BrandPalette
    {
        return BrandPalette::dark($this->primary, $this->secondary);
    }

    /**
     * Tronqué à deux décimales : un ratio sous le seuil ne s'affiche jamais « 4,50 ».
     */
    private static function ratio(float $contrast): string
    {
        return number_format(floor($contrast * 100) / 100, 2, ',', '');
    }
}
