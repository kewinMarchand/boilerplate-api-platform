<?php

declare(strict_types=1);

namespace App\Blog\Application;

use App\Blog\Domain\Article;
use App\Media\Domain\Image;

/**
 * Saisie d'un article, remplie par l'admin. La forme (slug, longueurs) est validée à la frontière.
 */
final class ArticleData
{
    public string $slug = '';
    public string $title = '';
    public string $excerpt = '';
    /** Texte de la fiche, en Markdown. */
    public string $body = '';
    public \DateTimeImmutable $publishedAt;
    /** Contenu du fichier envoyé, null pour garder l'image actuelle. */
    public ?string $imageContent = null;
    public string $imageAlt = '';
    public bool $imageDecorative = false;
    public ?Image $currentImage = null;

    public function __construct()
    {
        $this->publishedAt = new \DateTimeImmutable('today');
    }

    public static function fromArticle(Article $article): self
    {
        $data = new self();
        $data->slug = $article->getSlug();
        $data->title = $article->getTitle();
        $data->excerpt = $article->getExcerpt();
        $data->body = $article->getBody();
        $data->publishedAt = $article->getPublishedAt();
        $data->currentImage = $article->getImage();
        $data->imageAlt = $data->currentImage->alt ?? '';
        $data->imageDecorative = $data->currentImage?->isDecorative() ?? false;

        return $data;
    }
}
