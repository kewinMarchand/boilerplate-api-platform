<?php

declare(strict_types=1);

namespace App\Blog\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Blog\Domain\ArticleTermKind;
use App\Blog\Domain\ArticleTermRepository;
use App\Blog\Infrastructure\ApiPlatform\Resource\ArticleTermResource;

/**
 * Rubriques ou formats, selon la liste que l'opération désigne (extraProperties.kind).
 *
 * @implements ProviderInterface<ArticleTermResource>
 */
final readonly class ArticleTermCollectionProvider implements ProviderInterface
{
    public function __construct(private ArticleTermRepository $terms)
    {
    }

    /**
     * @return list<ArticleTermResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $kind = $operation->getExtraProperties()['kind'] ?? null;
        $kind = \is_string($kind) ? ArticleTermKind::from($kind) : throw new \LogicException('Opération sans liste de termes.');

        return array_map(ArticleTermResource::fromModel(...), $this->terms->findByKind($kind));
    }
}
