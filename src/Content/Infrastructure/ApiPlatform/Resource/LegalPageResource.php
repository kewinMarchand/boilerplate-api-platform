<?php

declare(strict_types=1);

namespace App\Content\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Content\Infrastructure\ApiPlatform\State\LegalPageProvider;
use App\Shared\Infrastructure\Locale\Alternate;

#[ApiResource(
    shortName: 'LegalPage',
    description: 'Page légale : mentions légales, données personnelles, déclaration d\'accessibilité.',
    operations: [
        new Get(
            uriTemplate: '/legal_pages/{slug}',
            description: 'Lit une page légale par son slug (mentions-legales, donnees-personnelles, accessibilite), commun à toutes les langues.',
            provider: LegalPageProvider::class,
        ),
    ],
)]
final readonly class LegalPageResource
{
    /**
     * @param list<LegalSection> $sections
     * @param list<Alternate>    $alternates
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
        #[ApiProperty(description: 'La page dans chaque langue activée, langue demandée comprise (même slug : il est fixé par les routes des fronts) ; tableau vide sur un site monolingue.', readableLink: true, required: true)]
        public array $alternates,
    ) {
    }
}
