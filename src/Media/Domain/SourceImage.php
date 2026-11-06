<?php

declare(strict_types=1);

namespace App\Media\Domain;

/**
 * Ce que l'encodeur lit d'un fichier envoyé, avant tout traitement : son type réel et ses dimensions, orientation
 * EXIF appliquée.
 */
final readonly class SourceImage
{
    public function __construct(
        public string $mimeType,
        public int $width,
        public int $height,
    ) {
    }
}
