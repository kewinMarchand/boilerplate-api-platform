<?php

declare(strict_types=1);

namespace App\Content\Domain;

/**
 * Titre et description du référencement d'une page fixe (ADR 0011), vides tant que l'admin n'en a pas saisi : le
 * front garde alors les siens. Une ligne par page, créée par la migration et les fixtures ; l'admin la modifie.
 */
class PageMeta
{
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
