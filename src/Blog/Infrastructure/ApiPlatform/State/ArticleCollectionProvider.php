<?php

declare(strict_types=1);

namespace App\Blog\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\State\ProviderInterface;
use App\Blog\Domain\Article;
use App\Blog\Domain\ArticleRepository;
use App\Blog\Domain\ArticleTerm;
use App\Blog\Infrastructure\ApiPlatform\Resource\ArticleResource;
use App\Media\Infrastructure\ApiPlatform\ImageViews;
use App\Shared\Infrastructure\Locale\ContentLocale;

/**
 * Filtres section et format par slug dans la langue demandée, en PHP sur la liste complète ; un slug que ne porte aucun article (inconnu
 * compris) donne une liste vide.
 *
 * @implements ProviderInterface<ArticleResource>
 */
final readonly class ArticleCollectionProvider implements ProviderInterface
{
    public function __construct(
        private ArticleRepository $articleRepository,
        private ImageViews $images,
        private ContentLocale $contentLocale,
    ) {
    }

    /**
     * @return list<ArticleResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $section = $this->slug($operation, 'section');
        $format = $this->slug($operation, 'format');
        $locale = $this->contentLocale->current();
        $articles = array_filter(
            $this->articleRepository->findLatestFirst(),
            static fn (Article $article): bool => self::matches($article->getSection(), $section, $locale) && self::matches($article->getFormat(), $format, $locale),
        );

        return array_values(array_map(fn (Article $article): ArticleResource => ArticleResource::fromModel($article, $this->images, $locale), $articles));
    }

    private function slug(Operation $operation, string $key): ?string
    {
        $value = $operation->getParameters()?->get($key, QueryParameter::class)?->getValue();

        return \is_string($value) && '' !== $value ? $value : null;
    }

    private static function matches(?ArticleTerm $term, ?string $slug, string $locale): bool
    {
        return null === $slug || $term?->getSlug($locale) === $slug;
    }
}
