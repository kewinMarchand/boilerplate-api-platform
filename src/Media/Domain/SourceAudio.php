<?php

declare(strict_types=1);

namespace App\Media\Domain;

/**
 * Ce que l'inspecteur lit d'un fichier audio envoyé : son format réel et sa durée.
 */
final readonly class SourceAudio
{
    public function __construct(
        public AudioFormat $format,
        public float $durationSeconds,
    ) {
    }
}
