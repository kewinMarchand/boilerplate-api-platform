<?php

declare(strict_types=1);

namespace App\Media\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;

/**
 * Fichier audio exposé dans le contrat public (ADR 0010).
 */
final readonly class AudioView
{
    public function __construct(
        #[ApiProperty(description: 'Adresse absolue du fichier, immuable.', required: true)]
        public string $src,
        #[ApiProperty(description: 'Type MIME, lu dans le contenu à l\'envoi.', required: true, schema: ['type' => 'string', 'enum' => ['audio/mpeg', 'audio/mp4', 'audio/ogg']])]
        public string $type,
        #[ApiProperty(description: 'Durée en secondes, arrondie, lue à l\'envoi.', required: true)]
        public int $durationSeconds,
    ) {
    }
}
