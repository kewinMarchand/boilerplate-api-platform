<?php

declare(strict_types=1);

namespace App\Content\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Content\Domain\MetaPage;
use App\Content\Domain\PageMetaRepository;
use App\Content\Infrastructure\ApiPlatform\Resource\PageMetaResource;
use App\Shared\Infrastructure\Locale\ContentLocale;

/**
 * Toutes les pages fixes, dans l'ordre de MetaPage ; une page sans ligne enregistrée sort avec des chaînes vides.
 *
 * @implements ProviderInterface<PageMetaResource>
 */
final readonly class PageMetaCollectionProvider implements ProviderInterface
{
    public function __construct(
        private PageMetaRepository $metas,
        private ContentLocale $contentLocale,
    ) {
    }

    /**
     * @return list<PageMetaResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $locale = $this->contentLocale->current();
        $saved = [];
        foreach ($this->metas->findAll() as $meta) {
            $saved[$meta->getPage()->value] = $meta;
        }

        return array_map(
            static fn (MetaPage $page): PageMetaResource => new PageMetaResource(
                $page->value,
                ($saved[$page->value] ?? null)?->getTitle($locale) ?? '',
                ($saved[$page->value] ?? null)?->getDescription($locale) ?? '',
            ),
            MetaPage::cases(),
        );
    }
}
