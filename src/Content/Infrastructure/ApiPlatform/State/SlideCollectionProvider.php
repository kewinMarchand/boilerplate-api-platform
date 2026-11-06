<?php

declare(strict_types=1);

namespace App\Content\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Content\Domain\Slide;
use App\Content\Domain\SlideRepository;
use App\Content\Infrastructure\ApiPlatform\Resource\SlideResource;
use App\Media\Infrastructure\ApiPlatform\ImageViews;

/**
 * @implements ProviderInterface<SlideResource>
 */
final readonly class SlideCollectionProvider implements ProviderInterface
{
    public function __construct(
        private SlideRepository $slides,
        private ImageViews $images,
    ) {
    }

    /**
     * @return list<SlideResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return array_map(fn (Slide $slide): SlideResource => SlideResource::fromModel($slide, $this->images), $this->slides->findInOrder());
    }
}
