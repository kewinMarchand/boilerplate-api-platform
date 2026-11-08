<?php

declare(strict_types=1);

namespace App\Blog\Application;

use App\Blog\Domain\ArticleTerm;
use App\Blog\Domain\ArticleTermKind;
use App\Blog\Domain\ArticleTermRepository;
use App\Blog\Domain\BlogRuleViolation;

/**
 * Rubrique et format choisis dans la saisie d'un article, communs à la création et à la modification.
 */
final class ArticleTerms
{
    /**
     * @return array{?ArticleTerm, ?ArticleTerm} rubrique, format
     *
     * @throws BlogRuleViolation rubrique ou format introuvable
     */
    public static function chosen(ArticleTermRepository $terms, ArticleData $data): array
    {
        return [self::find($terms, ArticleTermKind::Section, $data->sectionId), self::find($terms, ArticleTermKind::Format, $data->formatId)];
    }

    private static function find(ArticleTermRepository $terms, ArticleTermKind $kind, ?int $id): ?ArticleTerm
    {
        if (null === $id) {
            return null;
        }
        $term = $terms->find($id);

        return null !== $term && $term->getKind() === $kind ? $term : throw new BlogRuleViolation($kind->missing());
    }
}
