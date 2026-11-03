<?php

declare(strict_types=1);

namespace App\Blog\Application;

use App\Blog\Domain\Article;

/**
 * Saisie d'un article, remplie par l'admin. La forme (slug, longueurs) est validée à la frontière.
 */
final class ArticleData
{
    public string $slug = '';
    public string $title = '';
    public string $excerpt = '';
    public \DateTimeImmutable $publishedAt;

    public function __construct()
    {
        $this->publishedAt = new \DateTimeImmutable('today');
    }

    public static function fromArticle(Article $article): self
    {
        $data = new self();
        $data->slug = $article->getSlug();
        $data->title = $article->getTitle();
        $data->excerpt = $article->getExcerpt();
        $data->publishedAt = $article->getPublishedAt();

        return $data;
    }
}
