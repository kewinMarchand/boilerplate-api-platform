<?php

declare(strict_types=1);

namespace App\Blog\Application;

use App\Blog\Domain\ArticleTermKind;
use App\Blog\Domain\ArticleTermRepository;
use App\Blog\Domain\BlogRuleViolation;

final readonly class UpdateArticleTerm
{
    public function __construct(private ArticleTermRepository $terms)
    {
    }

    /**
     * @throws BlogRuleViolation terme introuvable dans la liste, slug déjà pris
     */
    public function __invoke(int $id, ArticleTermKind $kind, ArticleTermData $data): void
    {
        $term = $this->terms->find($id);
        if (null === $term || $term->getKind() !== $kind) {
            throw new BlogRuleViolation($kind->missing());
        }
        $sameSlug = $this->terms->findBySlug($kind, $data->slug);
        if (null !== $sameSlug && $sameSlug !== $term) {
            throw new BlogRuleViolation($kind->slugTaken($data->slug));
        }
        $term->update($data->slug, $data->name, $data->position);
        $this->terms->save($term);
    }
}
