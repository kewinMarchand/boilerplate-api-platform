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

    public function findBySlug(ArticleTermKind $kind, string $slug, ?string $locale = null): ?ArticleTerm
    {
        if (null === $locale) {
            return $this->entityManager->getRepository(ArticleTerm::class)->findOneBy(['kind' => $kind, 'slug' => $slug]);
        }
        // Les traductions vides ne sont pas gardées : sans slug traduit, celui de la langue par défaut
        $id = $this->entityManager->getConnection()->fetchOne(
            "SELECT id FROM article_term WHERE kind = :kind AND COALESCE(translations -> CAST(:locale AS text) ->> 'slug', slug) = :slug ORDER BY id LIMIT 1",
            ['kind' => $kind->value, 'locale' => $locale, 'slug' => $slug],
        );

        return is_numeric($id) ? $this->find((int) $id) : null;
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
