<?php

declare(strict_types=1);

namespace App\Blog\Domain;

use App\Media\Domain\Audio;
use App\Media\Domain\AudioFormat;
use App\Media\Domain\Image;

/**
 * Article du blog, avec son image envoyée par l'admin (ADR 0002), sa rubrique, son format, son fichier audio et son
 * intervenant (ADR 0010), ses métadonnées (SPEC-COMMUNE, section 26). L'unicité du slug se vérifie dans les cas d'usage.
 */
class Article
{
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
            throw new BlogRuleViolation(\sprintf('L\'intervenant compte %d caractères au plus.', self::SPEAKER_MAX_LENGTH));
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
    public function getSpeaker(): string
    {
        return $this->speaker;
    }

    public function getMetaTitle(): string
    {
        return $this->metaTitle;
    }

    public function getMetaDescription(): string
    {
        return $this->metaDescription;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getExcerpt(): string
    {
        return $this->excerpt;
    }

    public function getPublishedAt(): \DateTimeImmutable
    {
        return $this->publishedAt;
    }

    /**
     * Texte de la fiche, en Markdown saisi dans l'admin.
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Null pour un article d'avant l'envoi des images (ligne migrée), jusqu'à ce que l'admin lui en donne une.
     */
    public function getImage(): ?Image
    {
        return null === $this->imageKey ? null : new Image($this->imageKey, $this->imageAlt);
    }
}
