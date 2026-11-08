<?php

declare(strict_types=1);

namespace App\Media\Application;

use App\Media\Domain\Audio;
use App\Media\Domain\AudioInspector;
use App\Media\Domain\MediaRuleViolation;
use App\Media\Domain\MediaStorage;

/**
 * Contrôle un fichier audio envoyé et le stocke tel quel (ADR 0010) : 100 Mo au plus, format reconnu à son contenu,
 * durée lue dans ses en-têtes. Le fichier passe par son chemin, jamais en mémoire.
 */
final readonly class StoreAudio
{
    public const int MAX_BYTES = 100 * 1024 * 1024;

    public function __construct(
        private AudioInspector $inspector,
        private MediaStorage $storage,
    ) {
    }

    /**
     * @throws MediaRuleViolation
     */
    public function __invoke(string $path): Audio
    {
        $size = @filesize($path);
        if (false === $size) {
            throw new MediaRuleViolation('Le fichier audio n\'a pas pu être lu.');
        }
        if ($size > self::MAX_BYTES) {
            throw new MediaRuleViolation('Le fichier audio pèse plus de 100 Mo.');
        }
        $source = $this->inspector->inspect($path);
        if (null === $source) {
            throw new MediaRuleViolation('Le fichier ne se lit pas comme un fichier audio MP3, M4A ou OGG.');
        }

        $hash = hash_init('sha256');
        hash_update($hash, "audio\n");
        hash_update_file($hash, $path);
        $audio = new Audio(substr(hash_final($hash), 0, 32), $source->format, max(1, (int) round($source->durationSeconds)));
        if (!$this->storage->has($audio->fileName())) {
            $this->storage->putFile($audio->fileName(), $path);
        }

        return $audio;
    }
}
