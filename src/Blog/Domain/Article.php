<?php

declare(strict_types=1);

namespace App\Blog\Domain;

use App\Media\Domain\Audio;
use App\Media\Domain\AudioFormat;
use App\Media\Domain\Image;
use App\Shared\Domain\Translatable;

/**
 * Article du blog, avec son image envoyée par l'admin (ADR 0002), sa rubrique, son format, son fichier audio et son
 * intervenant (ADR 0010), ses métadonnées (SPEC-COMMUNE, section 26). L'unicité du slug, dans chaque langue, se vérifie
 * dans les cas d'usage. Traduisibles (ADR 0015) : slug, titre, chapô, texte, intervenant, métadonnées et texte de
 * remplacement de l'image.
 */
class Article
{
    use Translatable;

    public const int SPEAKER_MAX_LENGTH = 80;

    private ?int $id = null;
    private ?string $imageKey;
    private string $imageAlt;
    private ?ArticleTerm $section = null;
    private ?ArticleTerm $format = null;
    private ?string $audioKey = null;
    private ?AudioFormat $audioFormat = null;
    private ?int $audioDuration = null;
    private string $speaker = '';
    private string $metaTitle = '';
    private string $metaDescription = '';

    public function __construct(
        private string $slug,
        private string $title,
        private string $excerpt,
        private \DateTimeImmutable $publishedAt,
        Image $image,
        private string $body = '',
    ) {
        $this->imageKey = $image->key;
        $this->imageAlt = $image->alt;
    }

    public function update(string $slug, string $title, string $excerpt, \DateTimeImmutable $publishedAt, Image $image, string $body): void
    {
        $this->body = $body;
        $this->slug = $slug;
        $this->title = $title;
        $this->excerpt = $excerpt;
        $this->publishedAt = $publishedAt;
        $this->imageKey = $image->key;
        $this->imageAlt = $image->alt;
    }

    /**
     * @throws BlogRuleViolation terme de l'autre liste
     */
    public function classify(?ArticleTerm $section, ?ArticleTerm $format): void
    {
        if (null !== $section && ArticleTermKind::Section !== $section->getKind()) {
            throw new BlogRuleViolation('La rubrique choisie n\'est pas une rubrique.');
        }
        if (null !== $format && ArticleTermKind::Format !== $format->getKind()) {
            throw new BlogRuleViolation('Le format choisi n\'est pas un format.');
        }
        $this->section = $section;
        $this->format = $format;
    }

    /**
     * @throws BlogRuleViolation intervenant de plus de 80 caractères
     */
    public function changeAudio(?Audio $audio, string $speaker): void
    {
        $speaker = trim($speaker);
        if (mb_strlen($speaker) > self::SPEAKER_MAX_LENGTH) {
            throw new BlogRuleViolation('L\'intervenant compte %max% caractères au plus.', ['%max%' => self::SPEAKER_MAX_LENGTH]);
        }
        $this->audioKey = $audio?->key;
        $this->audioFormat = $audio?->format;
        $this->audioDuration = $audio?->durationSeconds;
        $this->speaker = $speaker;
    }

    public function changeMeta(string $title, string $description): void
    {
        $this->metaTitle = trim($title);
        $this->metaDescription = trim($description);
    }

    public function getSection(): ?ArticleTerm
    {
        return $this->section;
    }

    public function getFormat(): ?ArticleTerm
    {
        return $this->format;
    }

    public function getAudio(): ?Audio
    {
        return null === $this->audioKey || null === $this->audioFormat || null === $this->audioDuration
            ? null
            : new Audio($this->audioKey, $this->audioFormat, $this->audioDuration);
    }

    /** Vide sans intervenant. */
    public function getSpeaker(?string $locale = null): string
    {
        return $this->translated('speaker', $this->speaker, $locale);
    }

    public function getMetaTitle(?string $locale = null): string
    {
        return $this->translated('metaTitle', $this->metaTitle, $locale);
    }

    public function getMetaDescription(?string $locale = null): string
    {
        return $this->translated('metaDescription', $this->metaDescription, $locale);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(?string $locale = null): string
    {
        return $this->translated('slug', $this->slug, $locale);
    }

    public function getTitle(?string $locale = null): string
    {
        return $this->translated('title', $this->title, $locale);
    }

    public function getExcerpt(?string $locale = null): string
    {
        return $this->translated('excerpt', $this->excerpt, $locale);
    }

    public function getPublishedAt(): \DateTimeImmutable
    {
        return $this->publishedAt;
    }

    /**
     * Texte de la fiche, en Markdown saisi dans l'admin.
     */
    public function getBody(?string $locale = null): string
    {
        return $this->translated('body', $this->body, $locale);
    }

    /**
     * Null pour un article d'avant l'envoi des images (ligne migrée), jusqu'à ce que l'admin lui en donne une.
     */
    public function getImage(?string $locale = null): ?Image
    {
        // Une image décorative le reste dans toutes les langues
        $alt = '' === $this->imageAlt ? '' : $this->translated('imageAlt', $this->imageAlt, $locale);

        return null === $this->imageKey ? null : new Image($this->imageKey, $alt);
    }

    private function defaultTexts(): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'speaker' => $this->speaker,
            'metaTitle' => $this->metaTitle,
            'metaDescription' => $this->metaDescription,
            'imageAlt' => $this->imageAlt,
        ];
    }

    private function applyDefaultTexts(array $texts): void
    {
        $this->slug = $texts['slug'];
        $this->title = $texts['title'];
        $this->excerpt = $texts['excerpt'];
        $this->body = $texts['body'];
        $this->speaker = $texts['speaker'];
        $this->metaTitle = $texts['metaTitle'];
        $this->metaDescription = $texts['metaDescription'];
        $this->imageAlt = $texts['imageAlt'];
    }
}
