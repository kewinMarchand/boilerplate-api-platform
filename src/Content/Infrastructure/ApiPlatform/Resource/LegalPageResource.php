<?php

declare(strict_types=1);

namespace App\Content\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Content\Infrastructure\ApiPlatform\State\LegalPageProvider;

#[ApiResource(
    shortName: 'LegalPage',
    description: 'Page légale : mentions légales, données personnelles, déclaration d\'accessibilité.',
    operations: [
        new Get(
            uriTemplate: '/legal_pages/{slug}',
            description: 'Lit une page légale par son slug (mentions-legales, donnees-personnelles, accessibilite).',
            provider: LegalPageProvider::class,
        ),
    ],
)]
final readonly class LegalPageResource
{
    /**
     * @param list<LegalSection> $sections
     */
    public function __construct(
        #[ApiProperty(identifier: true, required: true)]
        public string $slug,
        #[ApiProperty(required: true)]
        public string $title,
        #[ApiProperty(description: 'HTML placé avant la première section, vide s\'il n\'y en a pas.', required: true)]
        public string $introduction,
        #[ApiProperty(required: true)]
        public array $sections,
    ) {
    }
}
