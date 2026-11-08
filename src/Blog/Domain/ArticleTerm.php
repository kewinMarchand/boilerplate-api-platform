<?php

declare(strict_types=1);

namespace App\Blog\Domain;

/**
 * Rubrique ou format d'article (ADR 0010) : slug unique dans sa liste, nom, position. L'unicité du slug et le refus de
 * supprimer un terme utilisé se vérifient dans les cas d'usage.
 */
class ArticleTerm
{
    private ?int $id = null;

    public function __construct(
        private ArticleTermKind $kind,
        private string $slug,
        private string $name,
        private int $position,
    ) {
    }

    public function update(string $slug, string $name, int $position): void
    {
        $this->slug = $slug;
        $this->name = $name;
        $this->position = $position;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKind(): ArticleTermKind
    {
        return $this->kind;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPosition(): int
    {
        return $this->position;
    }
}
