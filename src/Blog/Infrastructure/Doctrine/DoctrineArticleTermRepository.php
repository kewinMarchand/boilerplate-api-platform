<?php

declare(strict_types=1);

namespace App\Blog\Infrastructure\Doctrine;

use App\Blog\Domain\ArticleTerm;
use App\Blog\Domain\ArticleTermKind;
use App\Blog\Domain\ArticleTermRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineArticleTermRepository implements ArticleTermRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findByKind(ArticleTermKind $kind): array
    {
        return $this->entityManager->getRepository(ArticleTerm::class)->findBy(['kind' => $kind], ['position' => 'ASC', 'name' => 'ASC']);
    }

    public function find(int $id): ?ArticleTerm
    {
        return $this->entityManager->find(ArticleTerm::class, $id);
    }

    public function findBySlug(ArticleTermKind $kind, string $slug): ?ArticleTerm
    {
        return $this->entityManager->getRepository(ArticleTerm::class)->findOneBy(['kind' => $kind, 'slug' => $slug]);
    }

    public function save(ArticleTerm $term): void
    {
        $this->entityManager->persist($term);
        $this->entityManager->flush();
    }

    public function remove(ArticleTerm $term): void
    {
        $this->entityManager->remove($term);
        $this->entityManager->flush();
    }
}
