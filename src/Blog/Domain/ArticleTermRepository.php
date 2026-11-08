<?php

declare(strict_types=1);

namespace App\Blog\Domain;

interface ArticleTermRepository
{
    /**
     * @return list<ArticleTerm> par position, puis par nom
     */
    public function findByKind(ArticleTermKind $kind): array;

    public function find(int $id): ?ArticleTerm;

    public function findBySlug(ArticleTermKind $kind, string $slug): ?ArticleTerm;

    public function save(ArticleTerm $term): void;

    public function remove(ArticleTerm $term): void;
}
