<?php

declare(strict_types=1);

namespace App\Content\Domain;

use App\Shared\Domain\Translatable;

/**
 * Titre et description du référencement d'une page fixe (ADR 0011), vides tant que l'admin n'en a pas saisi : le
 * front garde alors les siens. Une ligne par page, créée par la migration et les fixtures ; l'admin la modifie.
 * Traduisibles (ADR 0015) : titre et description.
 */
class PageMeta
{
    use Translatable;

    private ?int $id = null;
    private string $title = '';
    private string $description = '';

    public function __construct(private MetaPage $page)
    {
    }

    public function update(string $title, string $description): void
    {
        $this->title = trim($title);
        $this->description = trim($description);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPage(): MetaPage
    {
        return $this->page;
    }

    public function getTitle(?string $locale = null): string
    {
        return $this->translated('title', $this->title, $locale);
    }

    public function getDescription(?string $locale = null): string
    {
        return $this->translated('description', $this->description, $locale);
    }

    private function defaultTexts(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
        ];
    }

    private function applyDefaultTexts(array $texts): void
    {
        $this->title = $texts['title'];
        $this->description = $texts['description'];
    }
}
