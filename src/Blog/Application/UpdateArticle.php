<?php

declare(strict_types=1);

namespace App\Blog\Application;

use App\Blog\Domain\ArticleRepository;
use App\Blog\Domain\BlogRuleViolation;

final readonly class UpdateArticle
{
    public function __construct(private ArticleRepository $articles)
    {
    }

    /**
     * @throws BlogRuleViolation article introuvable, slug déjà pris
     */
    public function __invoke(int $id, ArticleData $data): void
    {
        $article = $this->articles->find($id) ?? throw new BlogRuleViolation('Cet article n\'existe plus.');
        $sameSlug = $this->articles->findBySlug($data->slug);
        if (null !== $sameSlug && $sameSlug !== $article) {
            throw new BlogRuleViolation(\sprintf('Le slug « %s » est déjà pris par un autre article.', $data->slug));
        }

        $article->update($data->slug, $data->title, $data->excerpt, $data->publishedAt);
        $this->articles->save($article);
    }
}
