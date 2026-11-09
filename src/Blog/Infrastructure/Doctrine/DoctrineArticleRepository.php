<?php

declare(strict_types=1);

namespace App\Blog\Infrastructure\Doctrine;

use App\Blog\Domain\Article;
use App\Blog\Domain\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineArticleRepository implements ArticleRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findLatestFirst(): array
    {
        return $this->entityManager->getRepository(Article::class)->findBy([], ['publishedAt' => 'DESC', 'id' => 'DESC']);
    }

    public function find(int $id): ?Article
    {
        return $this->entityManager->find(Article::class, $id);
    }

    public function findBySlug(string $slug, ?string $locale = null): ?Article
    {
        if (null === $locale) {
            return $this->entityManager->getRepository(Article::class)->findOneBy(['slug' => $slug]);
        }
        // Les traductions vides ne sont pas gardées : sans slug traduit, celui de la langue par défaut
        $id = $this->entityManager->getConnection()->fetchOne(
            "SELECT id FROM article WHERE COALESCE(translations -> CAST(:locale AS text) ->> 'slug', slug) = :slug ORDER BY id LIMIT 1",
            ['locale' => $locale, 'slug' => $slug],
        );

        return is_numeric($id) ? $this->find((int) $id) : null;
    }

    public function save(Article $article): void
    {
        $this->entityManager->persist($article);
        $this->entityManager->flush();
    }

    public function remove(Article $article): void
    {
        $this->entityManager->remove($article);
        $this->entityManager->flush();
    }
}
