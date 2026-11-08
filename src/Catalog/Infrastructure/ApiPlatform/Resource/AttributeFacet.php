<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;

final readonly class AttributeFacet
{
    /**
     * @param list<AttributeFacetValue> $values
     */
    public function __construct(
        #[ApiProperty(description: 'Code de l\'attribut, clé du filtre attributes[<code>][].', required: true)]
        public string $code,
        #[ApiProperty(required: true)]
        public string $label,
        #[ApiProperty(description: 'Toutes les valeurs de l\'attribut, dans leur ordre, y compris à 0.', readableLink: true, required: true)]
        public array $values,
    ) {
    }
}
