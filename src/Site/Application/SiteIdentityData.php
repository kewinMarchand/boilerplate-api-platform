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
    /** Pas de couleurs de marque : les fronts gardent les jetons de boilerplates-ui ; les couleurs saisies sont ignorées. */
    public bool $useLibraryColors = true;
    /** #rrggbb. */
    public string $primaryColor = '';
    /** #rrggbb. */
    public string $secondaryColor = '';
    public DefaultColorScheme $defaultColorScheme = DefaultColorScheme::Auto;
    /** Contenu du logo envoyé, null pour garder le logo actuel. */
    public ?string $imageContent = null;
    /** Vide : le nom du site. */
    public string $imageAlt = '';
    /** Retire le logo actuel ; un fichier envoyé en même temps est ignoré. */
    public bool $removeLogo = false;
    /** Contenu de l'image de partage envoyée, null pour garder l'actuelle. */
    public ?string $shareImageContent = null;
    /** Vide : le nom du site. */
    public string $shareImageAlt = '';
    public bool $removeShareImage = false;
    /** Contenu de l'icône envoyée, null pour garder l'actuelle. */
    public ?string $iconContent = null;
    public bool $removeIcon = false;
    public bool $salesEnabled = true;
    public string $contactRecipient = '';

    public static function fromSiteIdentity(SiteIdentity $identity): self
    {
        $data = new self();
        $data->name = $identity->getName();
        $data->tagline = $identity->getTagline();
        $colors = $identity->getColors();
        $data->useLibraryColors = null === $colors;
        $data->primaryColor = $colors->primary->value ?? '';
        $data->secondaryColor = $colors->secondary->value ?? '';
        $data->defaultColorScheme = $identity->getDefaultColorScheme();
        $data->imageAlt = $identity->getLogo()->alt ?? '';
        $data->shareImageAlt = $identity->getShareImage()->alt ?? '';
        $data->salesEnabled = $identity->isSalesEnabled();
        $data->contactRecipient = $identity->getContactRecipient();

        return $data;
    }
}
