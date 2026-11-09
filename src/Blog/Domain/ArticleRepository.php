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

    /**
     * @param string|null $locale langue du slug (le sien, ou celui de la langue par défaut s'il n'est pas traduit) ;
     *                            null : la langue par défaut
     */
    public function findBySlug(string $slug, ?string $locale = null): ?Article;

    public function save(Article $article): void;

    public function remove(Article $article): void;
}
