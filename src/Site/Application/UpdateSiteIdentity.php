<?php

declare(strict_types=1);

namespace App\Site\Application;

use App\Media\Application\ChangeImage;
use App\Media\Domain\ImageUsage;
use App\Media\Domain\MediaRuleViolation;
use App\Site\Domain\BrandColors;
use App\Site\Domain\SiteIdentityRepository;
use App\Site\Domain\SiteRuleViolation;

final readonly class UpdateSiteIdentity
{
    public function __construct(
        private GetSiteIdentity $getSiteIdentity,
        private SiteIdentityRepository $identities,
        private ChangeImage $changeImage,
    ) {
    }

    /**
     * Les couleurs sont vérifiées avant l'encodage du logo : une saisie refusée ne laisse aucun fichier.
     *
     * @throws SiteRuleViolation  une seule couleur, couleur invalide ou illisible
     * @throws MediaRuleViolation logo refusé
     */
    public function __invoke(SiteIdentityData $data): void
    {
        $identity = ($this->getSiteIdentity)();
        $colors = BrandColors::fromInput($data->primaryColor, $data->secondaryColor);
        $alt = '' === trim($data->imageAlt) ? $data->name : $data->imageAlt;
        $logo = $data->removeLogo ? null : ($this->changeImage)($identity->getLogo(), $data->imageContent, $alt, false, ImageUsage::Logo);

        $identity->update($data->name, $data->tagline, $logo, $colors, $data->defaultColorScheme);
        $this->identities->save($identity);
    }
}
