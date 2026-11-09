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

    /**
     * @param string|null $locale langue du slug (le sien, ou celui de la langue par défaut s'il n'est pas traduit) ;
     *                            null : la langue par défaut
     */
    public function findBySlug(ArticleTermKind $kind, string $slug, ?string $locale = null): ?ArticleTerm;

    public function save(ArticleTerm $term): void;

    public function remove(ArticleTerm $term): void;
}
