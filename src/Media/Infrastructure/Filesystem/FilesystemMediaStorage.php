<?php

declare(strict_types=1);

namespace App\Media\Infrastructure\Filesystem;

use App\Media\Domain\MediaStorage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Variantes dans un dossier du volume de l'API (var/media), servi sous /media/ par Caddy (docker/media.caddy).
 */
final readonly class FilesystemMediaStorage implements MediaStorage
{
    public function __construct(
        #[Autowire('%app.media_dir%')]
        private string $directory,
    ) {
    }

    public function has(string $fileName): bool
    {
        return is_file($this->path($fileName));
    }

    public function put(string $fileName, string $content): void
    {
        if (!is_dir($this->directory) && !mkdir($this->directory, 0o775, true) && !is_dir($this->directory)) {
            throw new \RuntimeException(\sprintf('Dossier des médias impossible à créer : %s.', $this->directory));
        }
        // Écrit à côté puis renomme : Caddy ne sert jamais un fichier à moitié écrit
        $temporary = $this->path($fileName).'.'.bin2hex(random_bytes(4)).'.tmp';
        if (false === file_put_contents($temporary, $content) || !rename($temporary, $this->path($fileName))) {
            @unlink($temporary);
            throw new \RuntimeException(\sprintf('Variante impossible à enregistrer : %s.', $fileName));
        }
    }

    private function path(string $fileName): string
    {
        if (1 !== preg_match('/^[0-9a-f]{32}-\d+\.(avif|webp)$/', $fileName)) {
            throw new \InvalidArgumentException(\sprintf('Nom de variante inattendu : %s.', $fileName));
        }

        return $this->directory.'/'.$fileName;
    }
}
