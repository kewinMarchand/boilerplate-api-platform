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

    public function findBySlug(string $slug): ?Article
    {
        return $this->entityManager->getRepository(Article::class)->findOneBy(['slug' => $slug]);
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
