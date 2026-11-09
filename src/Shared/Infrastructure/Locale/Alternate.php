<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Locale;

use ApiPlatform\Metadata\ApiProperty;

/**
 * Version d'un élément dans une langue activée (SPEC-COMMUNE, section 29) : de quoi poser un hreflang ou un lien du
 * sélecteur de langue des fronts.
 */
final readonly class Alternate
{
    public function __construct(
        #[ApiProperty(description: 'Code de langue BCP 47 court (fr, en).', required: true)]
        public string $locale,
        #[ApiProperty(description: 'Slug de l\'élément dans cette langue : le sien, ou celui de la langue par défaut s\'il n\'est pas traduit.', required: true)]
        public string $slug,
    ) {
    }
}
