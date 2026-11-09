<?php

declare(strict_types=1);

namespace App\Blog\Application;

use App\Blog\Domain\ArticleTerm;
use App\Blog\Domain\ArticleTermKind;
use App\Blog\Domain\ArticleTermRepository;
use App\Blog\Domain\BlogRuleViolation;
use App\Shared\Domain\TranslatedSlugs;

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
        $taken = TranslatedSlugs::taken($data->slug, $data->translations, fn (string $slug, ?string $locale): ?ArticleTerm => $this->terms->findBySlug($kind, $slug, $locale), null);
        if (null !== $taken) {
            throw $kind->slugTaken($taken);
        }
        $term = new ArticleTerm($kind, $data->slug, $data->name, $data->position);
        $term->changeTranslations($data->translations);
        $this->terms->save($term);

        return $term;
    }
}
