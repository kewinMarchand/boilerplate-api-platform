<?php

declare(strict_types=1);

namespace App\Blog\Domain;

class Article
{
    private ?int $id = null;

    public function __construct(
        private string $slug,
        private string $title,
        private string $excerpt,
        private \DateTimeImmutable $publishedAt,
    ) {
    }

    public function update(string $slug, string $title, string $excerpt, \DateTimeImmutable $publishedAt): void
    {
        $this->slug = $slug;
        $this->title = $title;
        $this->excerpt = $excerpt;
        $this->publishedAt = $publishedAt;
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
}
