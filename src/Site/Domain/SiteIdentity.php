<?php

declare(strict_types=1);

namespace App\Site\Domain;

use App\Media\Domain\Image;

/**
 * Identité du site, une seule par API (SPEC-COMMUNE, section 22) : nom, accroche, logo, couleurs de marque et thème par
 * défaut. Sans couleurs de marque, les fronts gardent les jetons de boilerplates-ui.
 */
class SiteIdentity
{
    public const int ID = 1;
    public const string DEFAULT_NAME = 'Boilerplate';
    public const string DEFAULT_TAGLINE = 'Modèle de site avec boilerplates-ui, outillé pour la QA et les tests end-to-end.';

    private int $id = self::ID;
    private string $name;
    private string $tagline;
    private ?string $logoKey;
    private string $logoAlt;
    private ?int $logoHeight;
    private ?string $primaryColor;
    private ?string $secondaryColor;
    private DefaultColorScheme $defaultColorScheme;

    public function __construct(string $name, string $tagline, ?Image $logo, ?BrandColors $colors, DefaultColorScheme $defaultColorScheme)
    {
        $this->update($name, $tagline, $logo, $colors, $defaultColorScheme);
    }

    /**
     * Valeurs du modèle : celles des fixtures, et celles servies tant que l'admin n'a rien enregistré.
     */
    public static function byDefault(): self
    {
        return new self(self::DEFAULT_NAME, self::DEFAULT_TAGLINE, null, null, DefaultColorScheme::Auto);
    }

    public function update(string $name, string $tagline, ?Image $logo, ?BrandColors $colors, DefaultColorScheme $defaultColorScheme): void
    {
        $this->name = $name;
        $this->tagline = $tagline;
        $this->logoKey = $logo?->key;
        $this->logoAlt = $logo->alt ?? '';
        $this->logoHeight = $logo?->height;
        $this->primaryColor = $colors?->primary->value;
        $this->secondaryColor = $colors?->secondary->value;
        $this->defaultColorScheme = $defaultColorScheme;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTagline(): string
    {
        return $this->tagline;
    }

    public function getLogo(): ?Image
    {
        return null === $this->logoKey ? null : new Image($this->logoKey, $this->logoAlt, $this->logoHeight);
    }

    public function getColors(): ?BrandColors
    {
        return null === $this->primaryColor || null === $this->secondaryColor
            ? null
            : new BrandColors(new HexColor($this->primaryColor), new HexColor($this->secondaryColor));
    }

    public function getDefaultColorScheme(): DefaultColorScheme
    {
        return $this->defaultColorScheme;
    }
}
