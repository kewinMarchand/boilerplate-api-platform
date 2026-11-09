<?php

declare(strict_types=1);

namespace App\Blog\Application;

use App\Blog\Domain\ArticleRepository;
use App\Blog\Domain\ArticleTermRepository;
use App\Blog\Domain\BlogRuleViolation;
use App\Media\Application\ChangeImage;
use App\Media\Application\StoreAudio;
use App\Media\Domain\ImageUsage;
use App\Media\Domain\MediaRuleViolation;
use App\Shared\Domain\TranslatedSlugs;

final readonly class UpdateArticle
{
    public function __construct(
        private ArticleRepository $articles,
        private ArticleTermRepository $terms,
        private ChangeImage $changeImage,
        private StoreAudio $storeAudio,
    ) {
    }

    /**
     * @throws BlogRuleViolation  article introuvable, slug déjà pris, image absente, rubrique ou format introuvable, intervenant trop long
     * @throws MediaRuleViolation image ou fichier audio refusé
     */
    public function __invoke(int $id, ArticleData $data): void
    {
        $article = $this->articles->find($id) ?? throw new BlogRuleViolation('Cet article n\'existe plus.');
        $taken = TranslatedSlugs::taken($data->slug, $data->translations, $this->articles->findBySlug(...), $article);
        if (null !== $taken) {
            throw new BlogRuleViolation('Le slug « %slug% » est déjà pris par un autre article.', ['%slug%' => $taken]);
        }
        [$section, $format] = ArticleTerms::chosen($this->terms, $data);

        $image = ($this->changeImage)($article->getImage(), $data->imageContent, $data->imageAlt, $data->imageDecorative, ImageUsage::Article)
            ?? throw new BlogRuleViolation('Un article a besoin d\'une image.');
        $audio = match (true) {
            $data->removeAudio => null,
            null !== $data->audioPath => ($this->storeAudio)($data->audioPath),
            default => $article->getAudio(),
        };

        $article->update($data->slug, $data->title, $data->excerpt, $data->publishedAt, $image, $data->body);
        $article->classify($section, $format);
        $article->changeAudio($audio, $data->speaker);
        $article->changeMeta($data->metaTitle, $data->metaDescription);
        $article->changeTranslations($data->translations);
        $this->articles->save($article);
    }
}
