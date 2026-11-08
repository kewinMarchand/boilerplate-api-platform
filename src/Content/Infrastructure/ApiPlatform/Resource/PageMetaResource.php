<?php

declare(strict_types=1);

namespace App\Content\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Content\Infrastructure\ApiPlatform\State\PageMetaCollectionProvider;

#[ApiResource(
    shortName: 'PageMeta',
    description: 'Titre et description du référencement d\'une page fixe, saisis dans l\'admin (section « Référencement »).',
    operations: [
        new GetCollection(
            uriTemplate: '/page-metas',
            description: 'Liste les pages fixes, toutes les clés, chaînes vides quand rien n\'est saisi : le front garde alors ses valeurs.',
            provider: PageMetaCollectionProvider::class,
        ),
    ],
)]
final readonly class PageMetaResource
{
    public function __construct(
        #[ApiProperty(identifier: true, description: 'Clé de la page fixe.', required: true, schema: ['type' => 'string', 'enum' => ['home', 'catalog', 'blog', 'contact', 'sitemap', 'legal-notice', 'privacy', 'accessibility']])]
        public string $page,
        #[ApiProperty(description: 'Titre pour les moteurs de recherche, 60 caractères au plus ; chaîne vide si non saisi.', required: true)]
        public string $title,
        #[ApiProperty(description: 'Description pour les moteurs de recherche, 160 caractères au plus ; chaîne vide si non saisie.', required: true)]
        public string $description,
    ) {
    }
}
