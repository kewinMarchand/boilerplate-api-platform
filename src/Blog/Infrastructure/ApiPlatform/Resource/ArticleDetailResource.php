<?php

declare(strict_types=1);

namespace App\Blog\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Blog\Domain\Article;
use App\Blog\Infrastructure\ApiPlatform\State\ArticleDetailProvider;
use App\Media\Domain\ImageUsage;
use App\Media\Infrastructure\ApiPlatform\ImageView;
use App\Media\Infrastructure\ApiPlatform\ImageViews;

#[ApiResource(
    shortName: 'ArticleDetail',
    description: 'Fiche d\'un article du blog : les champs de la liste, plus son texte en HTML.',
    // image peut valoir null : sans cela, API Platform retire la clé alors que le schéma la déclare requise
    normalizationContext: ['skip_null_values' => false],
    operations: [
        new Get(
            uriTemplate: '/articles/{slug}',
            description: 'Lit un article par son slug.',
            provider: ArticleDetailProvider::class,
        ),
    ],
)]
final readonly class ArticleDetailResource
{
    public function __construct(
        #[ApiProperty(identifier: false, required: true)]
        public int $id,
        #[ApiProperty(identifier: true, required: true)]
        public string $slug,
        #[ApiProperty(required: true)]
        public string $title,
        #[ApiProperty(required: true)]
        public string $excerpt,
        #[ApiProperty(description: 'Date de publication (AAAA-MM-JJ).', required: true, schema: ['type' => 'string', 'format' => 'date'])]
        public string $publishedAt,
        #[ApiProperty(description: 'Image de l\'article (16:9), null tant que l\'admin n\'en a pas envoyé.', required: true)]
        public ?ImageView $image,
        #[ApiProperty(description: 'Texte de l\'article en HTML, converti du Markdown saisi (HTML saisi échappé, liens dangereux retirés). Vide s\'il n\'y en a pas.', required: true)]
        public string $html,
    ) {
    }

    public static function fromModel(Article $article, ImageViews $images, string $html): self
    {
        return new self(
            (int) $article->getId(),
            $article->getSlug(),
            $article->getTitle(),
            $article->getExcerpt(),
            $article->getPublishedAt()->format('Y-m-d'),
            $images->view($article->getImage(), ImageUsage::Article),
            $html,
        );
    }
}
