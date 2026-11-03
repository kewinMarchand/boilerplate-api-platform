<?php

declare(strict_types=1);

namespace App\Blog\Domain;

interface ArticleRepository
{
    /**
     * @return list<Article>
     */
    public function findLatestFirst(): array;

    public function find(int $id): ?Article;

    public function findBySlug(string $slug): ?Article;

    public function save(Article $article): void;

    public function remove(Article $article): void;
}
