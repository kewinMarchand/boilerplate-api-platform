<?php

declare(strict_types=1);

namespace App\Media\Application;

use App\Media\Domain\MediaRuleViolation;

/**
 * Fichier envoyé par l'admin (ou importé par les fixtures), avec son texte de remplacement.
 */
final readonly class ImageUpload
{
    public function __construct(
        public string $content,
        public string $alt,
        public bool $decorative = false,
    ) {
    }

    /**
     * Texte de remplacement à enregistrer : vide pour une image décorative, obligatoire sinon (RGAA 1.1 à 1.3).
     *
     * @throws MediaRuleViolation
     */
    public static function alt(string $alt, bool $decorative): string
    {
        if ($decorative) {
            return '';
        }
        $alt = trim($alt);
        if ('' === $alt) {
            throw new MediaRuleViolation('Le texte de remplacement est obligatoire, sauf pour une image décorative.');
        }

        return $alt;
    }
}
