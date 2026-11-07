<?php

declare(strict_types=1);

namespace App\Blog\Application;

use App\Blog\Domain\Article;
use App\Blog\Domain\ArticleRepository;
use App\Blog\Domain\BlogRuleViolation;
use App\Media\Application\ChangeImage;
use App\Media\Domain\ImageUsage;
use App\Media\Domain\MediaRuleViolation;

final readonly class CreateArticle
{
    public function __construct(
        private ArticleRepository $articles,
        private ChangeImage $changeImage,
    ) {
    }

    /**
     * @throws BlogRuleViolation  slug déjà pris, image absente
     * @throws MediaRuleViolation image refusée
     */
    public function __invoke(ArticleData $data): Article
    {
        if (null !== $this->articles->findBySlug($data->slug)) {
            throw new BlogRuleViolation(\sprintf('Le slug « %s » est déjà pris par un autre article.', $data->slug));
        }

        $image = ($this->changeImage)(null, $data->imageContent, $data->imageAlt, $data->imageDecorative, ImageUsage::Article)
            ?? throw new BlogRuleViolation('Un article a besoin d\'une image.');

        $article = new Article($data->slug, $data->title, $data->excerpt, $data->publishedAt, $image, $data->body);
        $this->articles->save($article);

        return $article;
    }
}
