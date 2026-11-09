<?php

declare(strict_types=1);

namespace App\Content\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Content\Domain\LegalPageRepository;
use App\Content\Infrastructure\ApiPlatform\Resource\LegalPageResource;
use App\Content\Infrastructure\ApiPlatform\Resource\LegalSection;
use App\Content\Infrastructure\Markdown\LegalPageSections;
use App\Shared\Infrastructure\Locale\ContentLocale;

/**
 * @implements ProviderInterface<LegalPageResource>
 */
final readonly class LegalPageProvider implements ProviderInterface
{
    public function __construct(
        private LegalPageRepository $pages,
        private LegalPageSections $sections,
        private ContentLocale $contentLocale,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?LegalPageResource
    {
        $slug = $uriVariables['slug'] ?? null;
        $page = \is_string($slug) ? $this->pages->findBySlug($slug) : null;
        if (null === $page) {
            return null;
        }
        $locale = $this->contentLocale->current();
        $content = $this->sections->split($page->getBody($locale));

        return new LegalPageResource(
            $page->getSlug(),
            $page->getTitle($locale),
            $content['introduction'],
            array_map(static fn (array $section): LegalSection => new LegalSection($section['title'], $section['html']), $content['sections']),
            $this->contentLocale->alternates(static fn (): string => $page->getSlug()),
        );
    }
}
