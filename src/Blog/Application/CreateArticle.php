<?php

declare(strict_types=1);

namespace App\Blog\Application;

use App\Blog\Domain\Article;
use App\Blog\Domain\ArticleRepository;
use App\Blog\Domain\BlogRuleViolation;

final readonly class CreateArticle
{
    public function __construct(private ArticleRepository $articles)
    {
    }

    /**
     * @throws BlogRuleViolation slug déjà pris
     */
    public function __invoke(ArticleData $data): Article
    {
        if (null !== $this->articles->findBySlug($data->slug)) {
            throw new BlogRuleViolation(\sprintf('Le slug « %s » est déjà pris par un autre article.', $data->slug));
        }

        $article = new Article($data->slug, $data->title, $data->excerpt, $data->publishedAt);
        $this->articles->save($article);

        return $article;
    }
}
