<?php

declare(strict_types=1);

namespace App\Content\Domain;

use App\Media\Domain\Image;

/**
 * Diapositive du carrousel de l'accueil, avec son image envoyée par l'admin (ADR 0002).
 * L'unicité du slug se vérifie dans les cas d'usage.
 */
class Slide
{
    private ?int $id = null;
    private ?string $imageKey;
    private string $imageAlt;

    public function __construct(
        private string $slug,
        private string $title,
        private string $text,
        Image $image,
        private int $position,
    ) {
        $this->imageKey = $image->key;
        $this->imageAlt = $image->alt;
    }

    public function update(string $slug, string $title, string $text, Image $image, int $position): void
    {
        $this->slug = $slug;
        $this->title = $title;
        $this->text = $text;
        $this->imageKey = $image->key;
        $this->imageAlt = $image->alt;
        $this->position = $position;
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

    public function getText(): string
    {
        return $this->text;
    }

    /**
     * Null pour une diapositive d'avant l'envoi des images (ligne migrée), jusqu'à ce que l'admin lui en donne une.
     */
    public function getImage(): ?Image
    {
        return null === $this->imageKey ? null : new Image($this->imageKey, $this->imageAlt);
    }

    public function getPosition(): int
    {
        return $this->position;
    }
}
