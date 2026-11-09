<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Doctrine;

use App\Catalog\Domain\Category;
use App\Catalog\Domain\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineCategoryRepository implements CategoryRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findAll(): array
    {
        return $this->entityManager->getRepository(Category::class)->findBy([], ['position' => 'ASC', 'id' => 'ASC']);
    }

    public function find(int $id): ?Category
    {
        return $this->entityManager->find(Category::class, $id);
    }

    public function findBySlug(string $slug, ?string $locale = null): ?Category
    {
        if (null === $locale) {
            return $this->entityManager->getRepository(Category::class)->findOneBy(['slug' => $slug]);
        }
        // Les traductions vides ne sont pas gardées : sans slug traduit, celui de la langue par défaut
        $id = $this->entityManager->getConnection()->fetchOne(
            "SELECT id FROM category WHERE COALESCE(translations -> CAST(:locale AS text) ->> 'slug', slug) = :slug ORDER BY id LIMIT 1",
            ['locale' => $locale, 'slug' => $slug],
        );

        return is_numeric($id) ? $this->find((int) $id) : null;
    }

    public function save(Category $category): void
    {
        $this->entityManager->persist($category);
        $this->entityManager->flush();
    }

    public function remove(Category $category): void
    {
        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }
}
