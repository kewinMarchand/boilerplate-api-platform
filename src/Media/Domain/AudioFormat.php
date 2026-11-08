<?php

declare(strict_types=1);

namespace App\Media\Domain;

/**
 * Format d'un fichier audio accepté (ADR 0010), reconnu à son contenu ; la valeur est l'extension du fichier stocké.
 */
enum AudioFormat: string
{
    case Mp3 = 'mp3';
    case M4a = 'm4a';
    case Ogg = 'ogg';

    public function mimeType(): string
    {
        return match ($this) {
            self::Mp3 => 'audio/mpeg',
            self::M4a => 'audio/mp4',
            self::Ogg => 'audio/ogg',
        };
    }
}
