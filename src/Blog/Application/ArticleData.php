<?php

declare(strict_types=1);

namespace App\Blog\Application;

use App\Blog\Domain\Article;
use App\Media\Domain\Audio;
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
    public ?int $sectionId = null;
    public ?int $formatId = null;
    /** Chemin du fichier audio envoyé (fichier temporaire de la requête), null pour garder l'audio actuel. */
    public ?string $audioPath = null;
    /** Retire l'audio actuel ; un fichier envoyé en même temps est ignoré. */
    public bool $removeAudio = false;
    public ?Audio $currentAudio = null;
    public string $speaker = '';
    public string $metaTitle = '';
    public string $metaDescription = '';
    /** @var array<string, array<string, string|null>> traductions (ADR 0015), par langue puis par champ ; vide sur un site monolingue */
    public array $translations = [];

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
        $data->sectionId = $article->getSection()?->getId();
        $data->formatId = $article->getFormat()?->getId();
        $data->currentAudio = $article->getAudio();
        $data->speaker = $article->getSpeaker();
        $data->metaTitle = $article->getMetaTitle();
        $data->metaDescription = $article->getMetaDescription();
        $data->translations = $article->getTranslations();

        return $data;
    }
}
