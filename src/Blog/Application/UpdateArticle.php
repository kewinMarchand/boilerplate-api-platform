<?php

declare(strict_types=1);

namespace App\Blog\Application;

use App\Blog\Domain\ArticleRepository;
use App\Blog\Domain\BlogRuleViolation;
use App\Media\Application\ChangeImage;
use App\Media\Domain\ImageUsage;
use App\Media\Domain\MediaRuleViolation;

final readonly class UpdateArticle
{
    public function __construct(
        private ArticleRepository $articles,
        private ChangeImage $changeImage,
    ) {
    }

    /**
     * @throws BlogRuleViolation  article introuvable, slug déjà pris, image absente
     * @throws MediaRuleViolation image refusée
     */
    public function __invoke(int $id, ArticleData $data): void
    {
        $article = $this->articles->find($id) ?? throw new BlogRuleViolation('Cet article n\'existe plus.');
        $sameSlug = $this->articles->findBySlug($data->slug);
        if (null !== $sameSlug && $sameSlug !== $article) {
            throw new BlogRuleViolation(\sprintf('Le slug « %s » est déjà pris par un autre article.', $data->slug));
        }

        $image = ($this->changeImage)($article->getImage(), $data->imageContent, $data->imageAlt, $data->imageDecorative, ImageUsage::Article)
            ?? throw new BlogRuleViolation('Un article a besoin d\'une image.');

        $article->update($data->slug, $data->title, $data->excerpt, $data->publishedAt, $image);
        $this->articles->save($article);
    }
}
