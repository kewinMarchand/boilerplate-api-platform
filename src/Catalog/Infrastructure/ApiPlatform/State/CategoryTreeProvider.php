<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Catalog\Domain\Category;
use App\Catalog\Domain\CategoryRepository;
use App\Catalog\Domain\CategoryTree;
use App\Catalog\Infrastructure\ApiPlatform\Resource\CategoryResource;
use App\Shared\Infrastructure\Locale\ContentLocale;

/**
 * @implements ProviderInterface<CategoryResource>
 */
final readonly class CategoryTreeProvider implements ProviderInterface
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private ContentLocale $contentLocale,
    ) {
    }

    /**
     * @return list<CategoryResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return $this->nodes(new CategoryTree($this->categoryRepository->findAll()), null, $this->contentLocale->current());
    }

    /**
     * @return list<CategoryResource>
     */
    private function nodes(CategoryTree $tree, ?Category $parent, string $locale): array
    {
        return array_map(
            fn (Category $category): CategoryResource => new CategoryResource(
                $category->getSlug($locale),
                $category->getName($locale),
                $parent?->getSlug($locale),
                $this->nodes($tree, $category, $locale),
                $this->contentLocale->alternates($category->getSlug(...)),
            ),
            $tree->childrenOf($parent),
        );
    }
}
