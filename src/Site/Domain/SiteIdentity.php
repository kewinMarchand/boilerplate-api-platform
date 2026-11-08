<?php

declare(strict_types=1);

namespace App\Site\Domain;

use App\Media\Domain\Image;

/**
 * Identité du site, une seule par API (SPEC-COMMUNE, section 22) : nom, accroche, logo, couleurs de marque et thème par
 * défaut, icône (favicons, ADR 0012) et image de partage (section 26, ADR 0011), vente en ligne et adresse qui reçoit les
 * messages de contact (section 27, ADR 0014). Sans couleurs de marque, les fronts gardent les jetons de boilerplates-ui ;
 * sans icône ni image de partage, leurs propres fichiers.
 */
class SiteIdentity
{
    public const int ID = 1;
    public const string DEFAULT_NAME = 'Boilerplate';
    public const string DEFAULT_TAGLINE = 'Modèle de site avec boilerplates-ui, outillé pour la QA et les tests end-to-end.';
    public const string DEFAULT_CONTACT_RECIPIENT = 'contact@example.com';

    private int $id = self::ID;
    private string $name;
    private string $tagline;
    private ?string $logoKey;
    private string $logoAlt;
    private ?int $logoHeight;
    private ?string $primaryColor;
    private ?string $secondaryColor;
    private DefaultColorScheme $defaultColorScheme;
    private ?string $shareImageKey;
    private string $shareImageAlt;
    private ?string $iconKey;
    private bool $salesEnabled = true;
    private string $contactRecipient = self::DEFAULT_CONTACT_RECIPIENT;

    public function __construct(string $name, string $tagline, ?Image $logo, ?BrandColors $colors, DefaultColorScheme $defaultColorScheme, ?Image $shareImage = null, ?Image $icon = null)
    {
        $this->update($name, $tagline, $logo, $colors, $defaultColorScheme, $shareImage, $icon);
    }

    /**
     * Valeurs du modèle : celles des fixtures, et celles servies tant que l'admin n'a rien enregistré.
     */
    public static function byDefault(): self
    {
        return new self(self::DEFAULT_NAME, self::DEFAULT_TAGLINE, null, null, DefaultColorScheme::Auto);
    }

    /**
     * @param Image|null $icon décorative : les favicons n'ont pas de texte de remplacement
     */
    public function update(string $name, string $tagline, ?Image $logo, ?BrandColors $colors, DefaultColorScheme $defaultColorScheme, ?Image $shareImage, ?Image $icon): void
    {
        $this->name = $name;
        $this->tagline = $tagline;
        $this->logoKey = $logo?->key;
        $this->logoAlt = $logo->alt ?? '';
        $this->logoHeight = $logo?->height;
        $this->primaryColor = $colors?->primary->value;
        $this->secondaryColor = $colors?->secondary->value;
        $this->defaultColorScheme = $defaultColorScheme;
        $this->shareImageKey = $shareImage?->key;
        $this->shareImageAlt = $shareImage->alt ?? '';
        $this->iconKey = $icon?->key;
    }

    /**
     * @param bool   $salesEnabled     false : pas de prix ni de panier, chaque produit propose une demande de renseignement
     * @param string $contactRecipient adresse qui reçoit les messages de contact, jamais exposée par l'API
     */
    public function changeSales(bool $salesEnabled, string $contactRecipient): void
    {
        $this->salesEnabled = $salesEnabled;
        $this->contactRecipient = $contactRecipient;
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

    public function getShareImage(): ?Image
    {
        return null === $this->shareImageKey ? null : new Image($this->shareImageKey, $this->shareImageAlt);
    }

    public function getIcon(): ?Image
    {
        return null === $this->iconKey ? null : new Image($this->iconKey, '');
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

    public function isSalesEnabled(): bool
    {
        return $this->salesEnabled;
    }

    public function getContactRecipient(): string
    {
        return $this->contactRecipient;
    }
}
