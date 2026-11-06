<?php

declare(strict_types=1);

namespace App\Media\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;

/**
 * Image exposée dans le contrat public (ADR 0002) : les fronts posent les sources dans un <picture>, sans construire
 * aucun chemin.
 */
final readonly class ImageView
{
    /**
     * @param list<ImageSource> $sources
     */
    public function __construct(
        #[ApiProperty(description: 'Texte de remplacement. Vide : image décorative.', required: true)]
        public string $alt,
        #[ApiProperty(description: 'Largeur de la plus grande variante, en pixels (attribut width).', required: true)]
        public int $width,
        #[ApiProperty(description: 'Hauteur de la plus grande variante, en pixels (attribut height).', required: true)]
        public int $height,
        #[ApiProperty(description: 'Sources du <picture>, AVIF puis WebP.', required: true)]
        public array $sources,
        #[ApiProperty(description: 'Adresse absolue de la plus petite variante WebP (attribut src de l\'<img>).', required: true)]
        public string $src,
    ) {
    }
}
