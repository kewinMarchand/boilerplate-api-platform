<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Doctrine;

use App\Catalog\Domain\Category;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineProductRepository implements ProductRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findAll(): array
    {
        /* @var list<Product> */
        return $this->entityManager->createQueryBuilder()
            ->select('product', 'category')
            ->from(Product::class, 'product')
            ->join('product.category', 'category')
            ->orderBy('product.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findFeatured(): array
    {
        /* @var list<Product> */
        return $this->entityManager->createQueryBuilder()
            ->select('product', 'category')
            ->from(Product::class, 'product')
            ->join('product.category', 'category')
            ->where('product.featured = true')
            ->orderBy('product.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function find(int $id): ?Product
    {
        return $this->entityManager->find(Product::class, $id);
    }

    public function findBySlug(string $slug, ?string $locale = null): ?Product
    {
        if (null === $locale) {
            return $this->entityManager->getRepository(Product::class)->findOneBy(['slug' => $slug]);
        }
        // Les traductions vides ne sont pas gardées : sans slug traduit, celui de la langue par défaut
        $id = $this->entityManager->getConnection()->fetchOne(
            "SELECT id FROM product WHERE COALESCE(translations -> CAST(:locale AS text) ->> 'slug', slug) = :slug ORDER BY id LIMIT 1",
            ['locale' => $locale, 'slug' => $slug],
        );

        return is_numeric($id) ? $this->find((int) $id) : null;
    }

    public function countInCategory(Category $category): int
    {
        return $this->entityManager->getRepository(Product::class)->count(['category' => $category]);
    }

    public function save(Product $product): void
    {
        $this->entityManager->persist($product);
        $this->entityManager->flush();
    }

    public function remove(Product $product): void
    {
        $this->entityManager->remove($product);
        $this->entityManager->flush();
    }
}
