<?php

declare(strict_types=1);

namespace App\Blog\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Blog\Domain\Article;
use App\Blog\Infrastructure\ApiPlatform\State\ArticleCollectionProvider;
use App\Media\Domain\ImageUsage;
use App\Media\Infrastructure\ApiPlatform\ImageView;
use App\Media\Infrastructure\ApiPlatform\ImageViews;

#[ApiResource(
    shortName: 'Article',
    description: 'Article du blog affiché sur la page d\'accueil.',
    // image peut valoir null : sans cela, API Platform retire la clé alors que le schéma la déclare requise
    normalizationContext: ['skip_null_values' => false],
    operations: [
        new GetCollection(
            description: 'Liste les articles, du plus récent au plus ancien, sans pagination.',
            provider: ArticleCollectionProvider::class,
        ),
    ],
)]
final readonly class ArticleResource
{
    public function __construct(
        #[ApiProperty(identifier: true, required: true)]
        public int $id,
        #[ApiProperty(required: true)]
        public string $slug,
        #[ApiProperty(required: true)]
        public string $title,
        #[ApiProperty(required: true)]
        public string $excerpt,
        #[ApiProperty(description: 'Date de publication (AAAA-MM-JJ).', required: true, schema: ['type' => 'string', 'format' => 'date'])]
        public string $publishedAt,
        #[ApiProperty(description: 'Image de l\'article (16:9), null tant que l\'admin n\'en a pas envoyé.', required: true)]
        public ?ImageView $image,
    ) {
    }

    public static function fromModel(Article $article, ImageViews $images): self
    {
        return new self(
            (int) $article->getId(),
            $article->getSlug(),
            $article->getTitle(),
            $article->getExcerpt(),
            $article->getPublishedAt()->format('Y-m-d'),
            $images->view($article->getImage(), ImageUsage::Article),
        );
    }
}
