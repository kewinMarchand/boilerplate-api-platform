<?php

declare(strict_types=1);

namespace App\Blog\Application;

use App\Blog\Domain\ArticleTerm;
use App\Blog\Domain\ArticleTermKind;
use App\Blog\Domain\ArticleTermRepository;
use App\Blog\Domain\BlogRuleViolation;

final readonly class CreateArticleTerm
{
    public function __construct(private ArticleTermRepository $terms)
    {
    }

    /**
     * @throws BlogRuleViolation slug déjà pris dans la liste
     */
    public function __invoke(ArticleTermKind $kind, ArticleTermData $data): ArticleTerm
    {
        if (null !== $this->terms->findBySlug($kind, $data->slug)) {
            throw new BlogRuleViolation($kind->slugTaken($data->slug));
        }
        $term = new ArticleTerm($kind, $data->slug, $data->name, $data->position);
        $this->terms->save($term);

        return $term;
    }
}
