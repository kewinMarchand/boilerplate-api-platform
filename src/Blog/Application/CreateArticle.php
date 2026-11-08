<?php

declare(strict_types=1);

namespace App\Blog\Application;

use App\Blog\Domain\Article;
use App\Blog\Domain\ArticleRepository;
use App\Blog\Domain\ArticleTermRepository;
use App\Blog\Domain\BlogRuleViolation;
use App\Media\Application\ChangeImage;
use App\Media\Application\StoreAudio;
use App\Media\Domain\ImageUsage;
use App\Media\Domain\MediaRuleViolation;

final readonly class CreateArticle
{
    public function __construct(
        private ArticleRepository $articles,
        private ArticleTermRepository $terms,
        private ChangeImage $changeImage,
        private StoreAudio $storeAudio,
    ) {
    }

    /**
     * @throws BlogRuleViolation  slug déjà pris, image absente, rubrique ou format introuvable, intervenant trop long
     * @throws MediaRuleViolation image ou fichier audio refusé
     */
    public function __invoke(ArticleData $data): Article
    {
        if (null !== $this->articles->findBySlug($data->slug)) {
            throw new BlogRuleViolation(\sprintf('Le slug « %s » est déjà pris par un autre article.', $data->slug));
        }
        [$section, $format] = ArticleTerms::chosen($this->terms, $data);

        $image = ($this->changeImage)(null, $data->imageContent, $data->imageAlt, $data->imageDecorative, ImageUsage::Article)
            ?? throw new BlogRuleViolation('Un article a besoin d\'une image.');
        $audio = $data->removeAudio || null === $data->audioPath ? null : ($this->storeAudio)($data->audioPath);

        $article = new Article($data->slug, $data->title, $data->excerpt, $data->publishedAt, $image, $data->body);
        $article->classify($section, $format);
        $article->changeAudio($audio, $data->speaker);
        $article->changeMeta($data->metaTitle, $data->metaDescription);
        $this->articles->save($article);

        return $article;
    }
}
