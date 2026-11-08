<?php

declare(strict_types=1);

namespace App\Blog\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use App\Blog\Domain\Article;
use App\Blog\Infrastructure\ApiPlatform\State\ArticleCollectionProvider;
use App\Media\Domain\ImageUsage;
use App\Media\Infrastructure\ApiPlatform\AudioView;
use App\Media\Infrastructure\ApiPlatform\ImageView;
use App\Media\Infrastructure\ApiPlatform\ImageViews;

#[ApiResource(
    shortName: 'Article',
    description: 'Article du blog affiché sur la page d\'accueil.',
    // image, section, format et audio peuvent valoir null : sans cela, API Platform retire la clé alors que le schéma la déclare requise
    normalizationContext: ['skip_null_values' => false],
    operations: [
        new GetCollection(
            description: 'Liste les articles, du plus récent au plus ancien, sans pagination.',
            provider: ArticleCollectionProvider::class,
            parameters: [
                'section' => new QueryParameter(
                    description: 'Slug de rubrique (GET /api/article-sections). Slug inconnu : liste vide.',
                    schema: ['type' => 'string'],
                ),
                'format' => new QueryParameter(
                    description: 'Slug de format (GET /api/article-formats). Slug inconnu : liste vide.',
                    schema: ['type' => 'string'],
                ),
            ],
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
        #[ApiProperty(description: 'Rubrique de l\'article, null s\'il n\'en a pas.', readableLink: true, required: true)]
        public ?ArticleTermLink $section,
        #[ApiProperty(description: 'Format de l\'article, null s\'il n\'en a pas.', readableLink: true, required: true)]
        public ?ArticleTermLink $format,
        #[ApiProperty(description: 'Fichier audio de l\'article, null s\'il n\'en a pas.', required: true)]
        public ?AudioView $audio,
        #[ApiProperty(description: 'Intervenant (« Présenté par … »), 80 caractères au plus ; chaîne vide s\'il n\'y en a pas.', required: true)]
        public string $speaker,
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
            ArticleTermLink::fromModel($article->getSection()),
            ArticleTermLink::fromModel($article->getFormat()),
            $images->audio($article->getAudio()),
            $article->getSpeaker(),
        );
    }
}
