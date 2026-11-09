<?php

declare(strict_types=1);

namespace App\Blog\Application;

use App\Blog\Domain\ArticleRepository;
use App\Blog\Domain\ArticleTermKind;
use App\Blog\Domain\ArticleTermRepository;

/**
 * Nouvelle langue par défaut du site (ADR 0015) : sur chaque article, rubrique et format, les traductions de la nouvelle langue passent dans
 * les colonnes et le texte actuel devient la traduction de l'ancienne. Appelé par l'admin avec la mise à jour de
 * l'identité, dans la même transaction.
 */
final readonly class SwitchBlogDefaultLocale
{
    public function __construct(
        private ArticleRepository $articles,
        private ArticleTermRepository $terms,
    ) {
    }

    public function __invoke(string $from, string $to): void
    {
        foreach ($this->articles->findLatestFirst() as $article) {
            $article->switchDefaultLocale($from, $to);
            $this->articles->save($article);
        }
        foreach (ArticleTermKind::cases() as $kind) {
            foreach ($this->terms->findByKind($kind) as $term) {
                $term->switchDefaultLocale($from, $to);
                $this->terms->save($term);
            }
        }
    }
}
