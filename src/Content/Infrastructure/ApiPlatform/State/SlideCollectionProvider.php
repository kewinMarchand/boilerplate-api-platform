<?php

declare(strict_types=1);

namespace App\Content\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Content\Domain\SlideRepository;
use App\Content\Infrastructure\ApiPlatform\Resource\SlideResource;

/**
 * @implements ProviderInterface<SlideResource>
 */
final readonly class SlideCollectionProvider implements ProviderInterface
{
    public function __construct(private SlideRepository $slides)
    {
    }

    /**
     * @return list<SlideResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return array_map(SlideResource::fromModel(...), $this->slides->findInOrder());
    }
}
