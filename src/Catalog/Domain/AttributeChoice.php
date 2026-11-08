<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

/**
 * Valeur d'un attribut à choix : son code, porté par les produits et les filtres, et son libellé.
 */
final readonly class AttributeChoice
{
    public function __construct(
        public string $value,
        public string $label,
    ) {
    }
}
