<?php

declare(strict_types=1);

namespace App\Blog\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Blog\Domain\ArticleTerm;
use App\Blog\Domain\ArticleTermKind;
use App\Blog\Domain\ArticleTermRepository;
use App\Blog\Infrastructure\ApiPlatform\Resource\ArticleTermResource;
use App\Shared\Infrastructure\Locale\ContentLocale;

/**
 * Rubriques ou formats, selon la liste que l'opération désigne (extraProperties.kind).
 *
 * @implements ProviderInterface<ArticleTermResource>
 */
final readonly class ArticleTermCollectionProvider implements ProviderInterface
{
    public function __construct(
        private ArticleTermRepository $terms,
        private ContentLocale $contentLocale,
    ) {
    }

    /**
     * @return list<ArticleTermResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $kind = $operation->getExtraProperties()['kind'] ?? null;
        $kind = \is_string($kind) ? ArticleTermKind::from($kind) : throw new \LogicException('Opération sans liste de termes.');

        $locale = $this->contentLocale->current();

        return array_map(static fn (ArticleTerm $term): ArticleTermResource => ArticleTermResource::fromModel($term, $locale), $this->terms->findByKind($kind));
    }
}
