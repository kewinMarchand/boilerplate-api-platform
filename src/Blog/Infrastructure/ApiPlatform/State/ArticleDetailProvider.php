<?php

declare(strict_types=1);

namespace App\Blog\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Blog\Domain\ArticleRepository;
use App\Blog\Infrastructure\ApiPlatform\Resource\ArticleDetailResource;
use App\Blog\Infrastructure\Markdown\ArticleHtml;
use App\Media\Infrastructure\ApiPlatform\ImageViews;
use App\Shared\Infrastructure\Locale\ContentLocale;

/**
 * Fiche d'un article par son slug dans la langue demandée ; null (404) pour un slug inconnu dans cette langue.
 *
 * @implements ProviderInterface<ArticleDetailResource>
 */
final readonly class ArticleDetailProvider implements ProviderInterface
{
    public function __construct(
        private ArticleRepository $articles,
        private ImageViews $images,
        private ArticleHtml $html,
        private ContentLocale $contentLocale,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?ArticleDetailResource
    {
        $slug = $uriVariables['slug'] ?? null;
        $locale = $this->contentLocale->current();
        $article = \is_string($slug) ? $this->articles->findBySlug($slug, $locale) : null;

        return null === $article
            ? null
            : ArticleDetailResource::fromModel($article, $this->images, $this->html->render($article->getBody($locale)), $locale, $this->contentLocale->alternates($article->getSlug(...)));
    }
}
