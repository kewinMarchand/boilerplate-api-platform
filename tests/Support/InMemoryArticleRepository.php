<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Blog\Domain\Article;
use App\Blog\Domain\ArticleRepository;

final class InMemoryArticleRepository implements ArticleRepository
{
    /** @var list<Article> */
    private array $articles = [];

    /**
     * @param list<Article> $articles enregistrés dans l'ordre, avec un identifiant attribué comme en base
     */
    public function __construct(array $articles = [])
    {
        foreach ($articles as $article) {
            $this->save($article);
        }
    }

    public function findLatestFirst(): array
    {
        return $this->articles;
    }

    public function find(int $id): ?Article
    {
        foreach ($this->articles as $article) {
            if ($article->getId() === $id) {
                return $article;
            }
        }

        return null;
    }

    public function findBySlug(string $slug, ?string $locale = null): ?Article
    {
        foreach ($this->articles as $article) {
            if ($article->getSlug($locale) === $slug) {
                return $article;
            }
        }

        return null;
    }

    public function save(Article $article): void
    {
        if (null === $article->getId()) {
            Identity::assign($article, \count($this->articles) + 1);
            $this->articles[] = $article;
        }
    }

    public function remove(Article $article): void
    {
        $this->articles = array_values(array_filter($this->articles, static fn (Article $kept): bool => $kept !== $article));
    }
}
