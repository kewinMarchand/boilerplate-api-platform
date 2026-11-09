<?php

declare(strict_types=1);

namespace App\Content\Domain;

use App\Shared\Domain\Translatable;

/**
 * Page légale (mentions légales, données personnelles, déclaration d'accessibilité). Son slug est fixé par les
 * routes des fronts, commun aux langues : l'admin modifie le titre et le corps (traduisibles, ADR 0015), sans créer ni
 * supprimer de page.
 */
class LegalPage
{
    use Translatable;

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

    public function getTitle(?string $locale = null): string
    {
        return $this->translated('title', $this->title, $locale);
    }

    public function getBody(?string $locale = null): string
    {
        return $this->translated('body', $this->body, $locale);
    }

    private function defaultTexts(): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
        ];
    }

    private function applyDefaultTexts(array $texts): void
    {
        $this->title = $texts['title'];
        $this->body = $texts['body'];
    }
}
