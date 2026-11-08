<?php

declare(strict_types=1);

namespace App\Media\Infrastructure\Fixtures;

use App\Media\Application\StoreAudio;
use App\Media\Domain\Audio;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Fichiers audio de démonstration (fixtures/audio, générés, sans droits), importés par le même chemin que les envois de
 * l'admin (ADR 0010).
 */
final readonly class DemoAudio
{
    public function __construct(
        private StoreAudio $storeAudio,
        #[Autowire('%kernel.project_dir%/fixtures/audio')]
        private string $directory,
    ) {
    }

    public function import(string $name): Audio
    {
        return ($this->storeAudio)($this->directory.'/'.$name.'.mp3');
    }
}
