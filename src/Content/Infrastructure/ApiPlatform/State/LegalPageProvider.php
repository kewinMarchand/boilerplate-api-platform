<?php

declare(strict_types=1);

namespace App\Content\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Content\Domain\LegalPageRepository;
use App\Content\Infrastructure\ApiPlatform\Resource\LegalPageResource;
use App\Content\Infrastructure\ApiPlatform\Resource\LegalSection;
use App\Content\Infrastructure\Markdown\LegalPageSections;

/**
 * @implements ProviderInterface<LegalPageResource>
 */
final readonly class LegalPageProvider implements ProviderInterface
{
    public function __construct(
        private LegalPageRepository $pages,
        private LegalPageSections $sections,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?LegalPageResource
    {
        $slug = $uriVariables['slug'] ?? null;
        $page = \is_string($slug) ? $this->pages->findBySlug($slug) : null;
        if (null === $page) {
            return null;
        }
        $content = $this->sections->split($page->getBody());

        return new LegalPageResource(
            $page->getSlug(),
            $page->getTitle(),
            $content['introduction'],
            array_map(static fn (array $section): LegalSection => new LegalSection($section['title'], $section['html']), $content['sections']),
        );
    }
}
