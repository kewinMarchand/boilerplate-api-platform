<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Catalog\Domain\Category;
use App\Catalog\Domain\CategoryRepository;

final class InMemoryCategoryRepository implements CategoryRepository
{
    /** @var list<Category> */
    private array $categories = [];

    /**
     * @param list<Category> $categories enregistrées dans l'ordre, avec un identifiant attribué comme en base
     */
    public function __construct(array $categories = [])
    {
        foreach ($categories as $category) {
            $this->save($category);
        }
    }

    public function findAll(): array
    {
        return $this->categories;
    }

    public function find(int $id): ?Category
    {
        foreach ($this->categories as $category) {
            if ($category->getId() === $id) {
                return $category;
            }
        }

        return null;
    }

    public function findBySlug(string $slug, ?string $locale = null): ?Category
    {
        foreach ($this->categories as $category) {
            if ($category->getSlug($locale) === $slug) {
                return $category;
            }
        }

        return null;
    }

    public function save(Category $category): void
    {
        if (null === $category->getId()) {
            Identity::assign($category, \count($this->categories) + 1);
            $this->categories[] = $category;
        }
    }

    public function remove(Category $category): void
    {
        $this->categories = array_values(array_filter($this->categories, static fn (Category $kept): bool => $kept !== $category));
    }
}
