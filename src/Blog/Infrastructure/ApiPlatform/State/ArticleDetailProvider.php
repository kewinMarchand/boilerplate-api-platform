<?php

declare(strict_types=1);

namespace App\Blog\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Blog\Domain\ArticleRepository;
use App\Blog\Infrastructure\ApiPlatform\Resource\ArticleDetailResource;
use App\Blog\Infrastructure\Markdown\ArticleHtml;
use App\Media\Infrastructure\ApiPlatform\ImageViews;

/**
 * Fiche d'un article par son slug ; null (404) pour un slug inconnu.
 *
 * @implements ProviderInterface<ArticleDetailResource>
 */
final readonly class ArticleDetailProvider implements ProviderInterface
{
    public function __construct(
        private ArticleRepository $articles,
        private ImageViews $images,
        private ArticleHtml $html,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?ArticleDetailResource
    {
        $slug = $uriVariables['slug'] ?? null;
        $article = \is_string($slug) ? $this->articles->findBySlug($slug) : null;

        return null === $article ? null : ArticleDetailResource::fromModel($article, $this->images, $this->html->render($article->getBody()));
    }
}
