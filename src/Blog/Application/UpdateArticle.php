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
        $sameSlug = $this->articles->findBySlug($data->slug);
        if (null !== $sameSlug && $sameSlug !== $article) {
            throw new BlogRuleViolation(\sprintf('Le slug « %s » est déjà pris par un autre article.', $data->slug));
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
        $this->articles->save($article);
    }
}
