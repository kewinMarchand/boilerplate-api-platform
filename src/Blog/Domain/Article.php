<?php

declare(strict_types=1);

namespace App\Blog\Domain;

use App\Media\Domain\Image;

/**
 * Article du blog, avec son image envoyée par l'admin (ADR 0002). L'unicité du slug se vérifie dans les cas d'usage.
 */
class Article
{
    private ?int $id = null;
    private ?string $imageKey;
    private string $imageAlt;

    public function __construct(
        private string $slug,
        private string $title,
        private string $excerpt,
        private \DateTimeImmutable $publishedAt,
        Image $image,
    ) {
        $this->imageKey = $image->key;
        $this->imageAlt = $image->alt;
    }

    public function update(string $slug, string $title, string $excerpt, \DateTimeImmutable $publishedAt, Image $image): void
    {
        $this->slug = $slug;
        $this->title = $title;
        $this->excerpt = $excerpt;
        $this->publishedAt = $publishedAt;
        $this->imageKey = $image->key;
        $this->imageAlt = $image->alt;
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
     * Null pour un article d'avant l'envoi des images (ligne migrée), jusqu'à ce que l'admin lui en donne une.
     */
    public function getImage(): ?Image
    {
        return null === $this->imageKey ? null : new Image($this->imageKey, $this->imageAlt);
    }
}
