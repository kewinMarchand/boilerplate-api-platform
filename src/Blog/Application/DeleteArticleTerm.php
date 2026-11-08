<?php

declare(strict_types=1);

namespace App\Blog\Application;

use App\Blog\Domain\Article;
use App\Blog\Domain\ArticleRepository;
use App\Blog\Domain\ArticleTermKind;
use App\Blog\Domain\ArticleTermRepository;
use App\Blog\Domain\BlogRuleViolation;

/**
 * Refuse de supprimer une rubrique ou un format porté par un article ; compte fait en PHP, comme les attributs (ADR 0007).
 */
final readonly class DeleteArticleTerm
{
    public function __construct(
        private ArticleTermRepository $terms,
        private ArticleRepository $articles,
    ) {
    }

    /**
     * @throws BlogRuleViolation terme introuvable dans la liste, ou utilisé
     */
    public function __invoke(int $id, ArticleTermKind $kind): void
    {
        $term = $this->terms->find($id);
        if (null === $term || $term->getKind() !== $kind) {
            throw new BlogRuleViolation($kind->missing());
        }
        $used = \count(array_filter(
            $this->articles->findLatestFirst(),
            static fn (Article $article): bool => $article->getSection() === $term || $article->getFormat() === $term,
        ));
        if ($used > 0) {
            throw new BlogRuleViolation($kind->usedBy($term->getName(), $used));
        }
        $this->terms->remove($term);
    }
}
