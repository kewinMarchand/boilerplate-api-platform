<?php

declare(strict_types=1);

namespace App\Media\Application;

use App\Media\Domain\Image;
use App\Media\Domain\ImageUsage;
use App\Media\Domain\MediaRuleViolation;

/**
 * Image d'un objet après une saisie de l'admin : le fichier envoyé s'il y en a un, sinon l'image actuelle avec son
 * nouveau texte de remplacement.
 */
final readonly class ChangeImage
{
    public function __construct(private StoreImage $storeImage)
    {
    }

    /**
     * @throws MediaRuleViolation
     */
    public function __invoke(?Image $current, ?string $content, string $alt, bool $decorative, ImageUsage $usage): ?Image
    {
        if (null !== $content) {
            return ($this->storeImage)(new ImageUpload($content, $alt, $decorative), $usage);
        }

        return $current?->withAlt(ImageUpload::alt($alt, $decorative));
    }
}
