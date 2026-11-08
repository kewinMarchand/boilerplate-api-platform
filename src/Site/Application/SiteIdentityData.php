<?php

declare(strict_types=1);

namespace App\Site\Application;

use App\Site\Domain\DefaultColorScheme;
use App\Site\Domain\SiteIdentity;

/**
 * Saisie de l'identité du site, remplie par l'admin. La forme (longueurs, format des couleurs) est validée à la frontière.
 */
final class SiteIdentityData
{
    public string $name = '';
    public string $tagline = '';
    /** #rrggbb, vide avec la secondaire pour garder les couleurs de boilerplates-ui. */
    public string $primaryColor = '';
    /** #rrggbb, vide avec la principale. */
    public string $secondaryColor = '';
    public DefaultColorScheme $defaultColorScheme = DefaultColorScheme::Auto;
    /** Contenu du logo envoyé, null pour garder le logo actuel. */
    public ?string $imageContent = null;
    /** Vide : le nom du site. */
    public string $imageAlt = '';
    /** Retire le logo actuel ; un fichier envoyé en même temps est ignoré. */
    public bool $removeLogo = false;

    public static function fromSiteIdentity(SiteIdentity $identity): self
    {
        $data = new self();
        $data->name = $identity->getName();
        $data->tagline = $identity->getTagline();
        $colors = $identity->getColors();
        $data->primaryColor = $colors->primary->value ?? '';
        $data->secondaryColor = $colors->secondary->value ?? '';
        $data->defaultColorScheme = $identity->getDefaultColorScheme();
        $data->imageAlt = $identity->getLogo()->alt ?? '';

        return $data;
    }
}
