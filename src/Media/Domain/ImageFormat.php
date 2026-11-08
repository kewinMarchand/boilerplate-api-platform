<?php

declare(strict_types=1);

namespace App\Media\Domain;

/**
 * Format d'une variante. Les photos sont en AVIF puis WebP, dans l'ordre où un navigateur doit les essayer ; les
 * icônes du site en PNG, le format que lisent tous les navigateurs et les écrans d'accueil (ADR 0012).
 */
enum ImageFormat: string
{
    case Avif = 'avif';
    case Webp = 'webp';
    case Png = 'png';

    public function mimeType(): string
    {
        return 'image/'.$this->value;
    }
}
