<?php

declare(strict_types=1);

namespace App\Blog\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Blog\Domain\Article;
use App\Blog\Infrastructure\ApiPlatform\State\ArticleDetailProvider;
use App\Media\Domain\ImageUsage;
use App\Media\Infrastructure\ApiPlatform\AudioView;
use App\Media\Infrastructure\ApiPlatform\ImageView;
use App\Media\Infrastructure\ApiPlatform\ImageViews;
use App\Shared\Infrastructure\Locale\Alternate;

#[ApiResource(
    shortName: 'ArticleDetail',
    description: 'Fiche d\'un article du blog : les champs de la liste, plus son texte en HTML et ses métadonnées.',
    // image, section, format et audio peuvent valoir null : sans cela, API Platform retire la clé alors que le schéma la déclare requise
    normalizationContext: ['skip_null_values' => false],
    operations: [
        new Get(
            uriTemplate: '/articles/{slug}',
            description: 'Lit un article par son slug dans la langue demandée (le sien, ou celui de la langue par défaut s\'il n\'est pas traduit). Slug d\'une autre langue : 404.',
            provider: ArticleDetailProvider::class,
        ),
    ],
)]
final readonly class ArticleDetailResource
{
    /**
     * @param list<Alternate> $alternates
     */
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
        #[ApiProperty(description: 'Rubrique de l\'article, null s\'il n\'en a pas.', readableLink: true, required: true)]
        public ?ArticleTermLink $section,
        #[ApiProperty(description: 'Format de l\'article, null s\'il n\'en a pas.', readableLink: true, required: true)]
        public ?ArticleTermLink $format,
        #[ApiProperty(description: 'Fichier audio de l\'article, null s\'il n\'en a pas.', required: true)]
        public ?AudioView $audio,
        #[ApiProperty(description: 'Intervenant (« Présenté par … »), 80 caractères au plus ; chaîne vide s\'il n\'y en a pas.', required: true)]
        public string $speaker,
        #[ApiProperty(description: 'Titre pour les moteurs de recherche, 60 caractères au plus ; chaîne vide si l\'admin n\'en a pas saisi.', required: true)]
        public string $metaTitle,
        #[ApiProperty(description: 'Description pour les moteurs de recherche, 160 caractères au plus ; chaîne vide si l\'admin n\'en a pas saisi.', required: true)]
        public string $metaDescription,
        #[ApiProperty(description: 'L\'article dans chaque langue activée, langue demandée comprise, pour les hreflang et le sélecteur de langue ; tableau vide sur un site monolingue.', readableLink: true, required: true)]
        public array $alternates,
    ) {
    }

    /**
     * @param list<Alternate> $alternates
     */
    public static function fromModel(Article $article, ImageViews $images, string $html, string $locale, array $alternates): self
    {
        return new self(
            (int) $article->getId(),
            $article->getSlug($locale),
            $article->getTitle($locale),
            $article->getExcerpt($locale),
            $article->getPublishedAt()->format('Y-m-d'),
            $images->view($article->getImage($locale), ImageUsage::Article),
            $html,
            ArticleTermLink::fromModel($article->getSection(), $locale),
            ArticleTermLink::fromModel($article->getFormat(), $locale),
            $images->audio($article->getAudio()),
            $article->getSpeaker($locale),
            $article->getMetaTitle($locale),
            $article->getMetaDescription($locale),
            $alternates,
        );
    }
}
