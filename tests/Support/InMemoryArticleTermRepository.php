<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Blog\Domain\ArticleTerm;
use App\Blog\Domain\ArticleTermKind;
use App\Blog\Domain\ArticleTermRepository;

final class InMemoryArticleTermRepository implements ArticleTermRepository
{
    /** @var list<ArticleTerm> */
    private array $terms = [];
    private int $lastId = 0;

    /**
     * @param list<ArticleTerm> $terms enregistrés dans l'ordre, avec un identifiant attribué comme en base
     */
    public function __construct(array $terms = [])
    {
        foreach ($terms as $term) {
            $this->save($term);
        }
    }

    public function findByKind(ArticleTermKind $kind): array
    {
        $terms = array_values(array_filter($this->terms, static fn (ArticleTerm $term): bool => $term->getKind() === $kind));
        usort($terms, static fn (ArticleTerm $a, ArticleTerm $b): int => [$a->getPosition(), $a->getName()] <=> [$b->getPosition(), $b->getName()]);

        return $terms;
    }

    public function find(int $id): ?ArticleTerm
    {
        foreach ($this->terms as $term) {
            if ($term->getId() === $id) {
                return $term;
            }
        }

        return null;
    }

    public function findBySlug(ArticleTermKind $kind, string $slug, ?string $locale = null): ?ArticleTerm
    {
        foreach ($this->terms as $term) {
            if ($term->getKind() === $kind && $term->getSlug($locale) === $slug) {
                return $term;
            }
        }

        return null;
    }

    public function save(ArticleTerm $term): void
    {
        if (null === $term->getId()) {
            Identity::assign($term, ++$this->lastId);
            $this->terms[] = $term;
        }
    }

    public function remove(ArticleTerm $term): void
    {
        $this->terms = array_values(array_filter($this->terms, static fn (ArticleTerm $kept): bool => $kept !== $term));
    }
}
