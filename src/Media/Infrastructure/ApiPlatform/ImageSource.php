<?php

declare(strict_types=1);

namespace App\Media\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;

/**
 * Une source d'un élément <picture> : un format et toutes ses largeurs.
 */
final readonly class ImageSource
{
    public function __construct(
        #[ApiProperty(description: 'Type MIME de la source : image/avif, puis image/webp.', required: true, schema: ['type' => 'string', 'enum' => ['image/avif', 'image/webp']])]
        public string $type,
        #[ApiProperty(description: 'Valeur de l\'attribut srcset : adresses absolues et largeurs, de la plus petite à la plus grande.', required: true)]
        public string $srcset,
    ) {
    }
}
