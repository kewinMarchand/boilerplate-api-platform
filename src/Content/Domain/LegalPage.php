<?php

declare(strict_types=1);

namespace App\Content\Domain;

/**
 * Page légale (mentions légales, données personnelles, déclaration d'accessibilité). Son slug est fixé par les
 * routes des fronts : l'admin modifie le titre et le corps, sans créer ni supprimer de page.
 */
class LegalPage
{
    private ?int $id = null;

    /**
     * @param string $body Markdown ; chaque titre de niveau 2 ouvre une section
     */
    public function __construct(
        private string $slug,
        private string $title,
        private string $body,
    ) {
    }

    public function update(string $title, string $body): void
    {
        $this->title = $title;
        $this->body = $body;
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

    public function getBody(): string
    {
        return $this->body;
    }
}
