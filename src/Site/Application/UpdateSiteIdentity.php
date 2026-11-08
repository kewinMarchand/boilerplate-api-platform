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
     * Les couleurs sont vérifiées avant l'encodage des images : une couleur refusée ne laisse aucun fichier.
     *
     * @throws SiteRuleViolation  une seule couleur, couleur invalide ou illisible
     * @throws MediaRuleViolation logo, image de partage ou icône refusé
     */
    public function __invoke(SiteIdentityData $data): void
    {
        $identity = ($this->getSiteIdentity)();
        $colors = $data->useLibraryColors ? null : BrandColors::fromInput($data->primaryColor, $data->secondaryColor);
        $logo = $data->removeLogo ? null : ($this->changeImage)($identity->getLogo(), $data->imageContent, self::altOrName($data->imageAlt, $data->name), false, ImageUsage::Logo);
        $shareImage = $data->removeShareImage ? null : ($this->changeImage)($identity->getShareImage(), $data->shareImageContent, self::altOrName($data->shareImageAlt, $data->name), false, ImageUsage::Share);
        $icon = $data->removeIcon ? null : ($this->changeImage)($identity->getIcon(), $data->iconContent, '', true, ImageUsage::Icon);

        $identity->update($data->name, $data->tagline, $logo, $colors, $data->defaultColorScheme, $shareImage, $icon);
        $identity->changeSales($data->salesEnabled, $data->contactRecipient);
        $this->identities->save($identity);
    }

    private static function altOrName(string $alt, string $name): string
    {
        return '' === trim($alt) ? $name : $alt;
    }
}
