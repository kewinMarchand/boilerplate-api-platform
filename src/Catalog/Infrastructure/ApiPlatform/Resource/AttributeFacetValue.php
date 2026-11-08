<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;

final readonly class AttributeFacetValue
{
    public function __construct(
        #[ApiProperty(required: true)]
        public string $value,
        #[ApiProperty(required: true)]
        public string $label,
        #[ApiProperty(description: 'Nombre de produits obtenus en ajoutant cette valeur aux filtres des autres facettes et des autres attributs.', required: true)]
        public int $count,
    ) {
    }
}
