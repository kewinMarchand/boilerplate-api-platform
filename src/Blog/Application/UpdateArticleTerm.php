<?php

declare(strict_types=1);

namespace App\Blog\Application;

use App\Blog\Domain\ArticleTerm;
use App\Blog\Domain\ArticleTermKind;
use App\Blog\Domain\ArticleTermRepository;
use App\Blog\Domain\BlogRuleViolation;
use App\Shared\Domain\TranslatedSlugs;

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
            throw $kind->missing();
        }
        $taken = TranslatedSlugs::taken($data->slug, $data->translations, fn (string $slug, ?string $locale): ?ArticleTerm => $this->terms->findBySlug($kind, $slug, $locale), $term);
        if (null !== $taken) {
            throw $kind->slugTaken($taken);
        }
        $term->update($data->slug, $data->name, $data->position);
        $term->changeTranslations($data->translations);
        $this->terms->save($term);
    }
}
