<?php

declare(strict_types=1);

namespace App\Media\Infrastructure\Fixtures;

use App\Media\Application\ImageUpload;
use App\Media\Application\StoreImage;
use App\Media\Domain\Image;
use App\Media\Domain\ImageUsage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Photos de démonstration (fixtures/images, copiées de boilerplates-commun/shared-assets), importées par le même
 * chemin que les envois de l'admin : contrôles, variantes, stockage (ADR 0002).
 */
final readonly class DemoImages
{
    public function __construct(
        private StoreImage $storeImage,
        #[Autowire('%kernel.project_dir%/fixtures/images')]
        private string $directory,
    ) {
    }

    public function import(string $name, ImageUsage $usage, string $alt, bool $decorative = false): Image
    {
        $content = file_get_contents($this->directory.'/'.$name.'.webp');
        if (false === $content) {
            throw new \RuntimeException(\sprintf('Photo de démonstration introuvable : %s.', $name));
        }

        return ($this->storeImage)(new ImageUpload($content, $alt, $decorative), $usage);
    }
}
