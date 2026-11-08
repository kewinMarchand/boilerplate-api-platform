<?php

declare(strict_types=1);

namespace App\Site\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Media\Infrastructure\ApiPlatform\ImageView;
use App\Shared\Infrastructure\ApiPlatform\Scenario\EmptyScenarioResult;
use App\Site\Domain\SiteIdentity;
use App\Site\Infrastructure\ApiPlatform\State\SiteIdentityProvider;

#[ApiResource(
    shortName: 'SiteIdentity',
    description: 'Identité du site, saisie dans l\'admin : nom, accroche, logo, image de partage, favicons, couleurs de marque calculées (ou null), thème par défaut et vente en ligne.',
    // logo, shareImage, icon et theme peuvent valoir null : sans cela, API Platform retire la clé alors que le schéma la déclare requise
    normalizationContext: ['skip_null_values' => false],
    operations: [
        new Get(
            uriTemplate: '/site-identity',
            description: 'Lit l\'identité du site.',
            provider: SiteIdentityProvider::class,
        ),
    ],
)]
final readonly class SiteIdentityResource implements EmptyScenarioResult
{
    public function __construct(
        #[ApiProperty(description: 'Nom du site, 1 à 60 caractères.', required: true)]
        public string $name,
        #[ApiProperty(description: 'Accroche, 0 à 160 caractères.', required: true)]
        public string $tagline,
        #[ApiProperty(description: 'Logo, ratio du fichier gardé (largeurs 200 et 400), null sans logo : les fronts affichent alors le pictogramme et le nom.', required: true)]
        public ?ImageView $logo,
        #[ApiProperty(description: 'Image de partage (og:image, twitter:image) par défaut, 1200 × 630, null sans image : les fronts gardent la leur.', required: true)]
        public ?ImageView $shareImage,
        #[ApiProperty(description: 'Favicons en PNG (32, 180, 192 et 512 px), null sans icône : les fronts gardent leurs fichiers.', required: true)]
        public ?SiteIcon $icon,
        #[ApiProperty(description: 'Thème servi tant que le visiteur n\'a rien choisi.', required: true, schema: ['type' => 'string', 'enum' => ['auto', 'light', 'dark']])]
        public string $defaultColorScheme,
        #[ApiProperty(description: 'Jetons de couleur de marque calculés, null sans couleurs saisies : le front garde alors ceux de boilerplates-ui.', required: true)]
        public ?SiteTheme $theme,
        #[ApiProperty(description: 'Vente en ligne : false, les fronts n\'affichent ni prix ni panier et chaque produit propose une demande de renseignement.', required: true)]
        public bool $salesEnabled,
    ) {
    }

    public static function fromModel(SiteIdentity $identity, ?ImageView $logo, ?ImageView $shareImage, ?SiteIcon $icon): self
    {
        return new self(
            $identity->getName(),
            $identity->getTagline(),
            $logo,
            $shareImage,
            $icon,
            $identity->getDefaultColorScheme()->value,
            SiteTheme::fromColors($identity->getColors()),
            $identity->isSalesEnabled(),
        );
    }

    public static function emptyScenarioResult(): static
    {
        return self::fromModel(SiteIdentity::byDefault(), null, null, null);
    }
}
