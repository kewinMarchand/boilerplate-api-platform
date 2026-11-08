<?php

declare(strict_types=1);

namespace App\Media\Domain;

interface AudioInspector
{
    /**
     * Format réel et durée du fichier, lus dans son contenu, ou null s'il n'est ni MP3, ni M4A (audio seul), ni OGG.
     */
    public function inspect(string $path): ?SourceAudio;
}
