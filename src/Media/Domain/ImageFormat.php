<?php

declare(strict_types=1);

namespace App\Media\Domain;

/**
 * Format d'une variante, dans l'ordre où un navigateur doit les essayer.
 */
enum ImageFormat: string
{
    case Avif = 'avif';
    case Webp = 'webp';

    public function mimeType(): string
    {
        return 'image/'.$this->value;
    }
}
