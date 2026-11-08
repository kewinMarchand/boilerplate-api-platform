<?php

declare(strict_types=1);

namespace App\Blog\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Blog\Domain\ArticleTerm;
use App\Blog\Domain\ArticleTermKind;
use App\Blog\Infrastructure\ApiPlatform\State\ArticleTermCollectionProvider;

#[ApiResource(
    shortName: 'ArticleTerm',
    description: 'Rubrique ou format d\'article, administré dans l\'admin (ADR 0010).',
    operations: [
        new GetCollection(
            uriTemplate: '/article-sections',
            description: 'Liste les rubriques par position, y compris celles qu\'aucun article ne porte.',
            provider: ArticleTermCollectionProvider::class,
            extraProperties: ['kind' => ArticleTermKind::Section->value],
        ),
        new GetCollection(
            uriTemplate: '/article-formats',
            description: 'Liste les formats par position, y compris ceux qu\'aucun article ne porte.',
            provider: ArticleTermCollectionProvider::class,
            extraProperties: ['kind' => ArticleTermKind::Format->value],
        ),
    ],
)]
final readonly class ArticleTermResource
{
    public function __construct(
        #[ApiProperty(identifier: true, description: 'Slug, unique dans sa liste : valeur des filtres section et format de GET /api/articles.', required: true)]
        public string $slug,
        #[ApiProperty(required: true)]
        public string $name,
    ) {
    }

    public static function fromModel(ArticleTerm $term): self
    {
        return new self($term->getSlug(), $term->getName());
    }
}
