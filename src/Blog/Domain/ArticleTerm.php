<?php

declare(strict_types=1);

namespace App\Blog\Domain;

use App\Shared\Domain\Translatable;

/**
 * Rubrique ou format d'article (ADR 0010) : slug unique dans sa liste, nom, position. L'unicité du slug et le refus de
 * supprimer un terme utilisé se vérifient dans les cas d'usage. Traduisibles (ADR 0015) : slug (unique dans sa liste et
 * dans chaque langue) et nom.
 */
class ArticleTerm
{
    use Translatable;

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

    public function getSlug(?string $locale = null): string
    {
        return $this->translated('slug', $this->slug, $locale);
    }

    public function getName(?string $locale = null): string
    {
        return $this->translated('name', $this->name, $locale);
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    private function defaultTexts(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
        ];
    }

    private function applyDefaultTexts(array $texts): void
    {
        $this->slug = $texts['slug'];
        $this->name = $texts['name'];
    }
}
