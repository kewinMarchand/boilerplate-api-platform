<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

use App\Shared\Domain\Translatable;

/**
 * Agrégat : une catégorie protège sa place dans l'arbre (pas de cycle, 3 niveaux au plus).
 * L'unicité du slug (dans chaque langue) et la suppression se vérifient dans les cas d'usage, qui voient les autres
 * catégories. Traduisibles (ADR 0015) : nom et slug.
 */
class Category
{
    use Translatable;

    public const int MAX_DEPTH = 3;

    private ?int $id = null;

    public function __construct(
        private string $slug,
        private string $name,
        private int $position,
        private ?self $parent = null,
    ) {
        $this->guardParent($parent, 0);
    }

    /**
     * @param int $levelsBelow profondeur du sous-arbre de cette catégorie (0 sans enfant), qui descend avec elle
     *
     * @throws CatalogRuleViolation
     */
    public function update(string $slug, string $name, int $position, ?self $parent, int $levelsBelow): void
    {
        $this->guardParent($parent, $levelsBelow);
        $this->slug = $slug;
        $this->name = $name;
        $this->position = $position;
        $this->parent = $parent;
    }

    /**
     * Niveau de la catégorie dans l'arbre : 1 pour une racine.
     */
    public function depth(): int
    {
        return null === $this->parent ? 1 : $this->parent->depth() + 1;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getParent(): ?self
    {
        return $this->parent;
    }

    private function defaultTexts(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
        ];
    }

    private function applyDefaultTexts(array $texts): void
    {
        $this->name = $texts['name'];
        $this->slug = $texts['slug'];
    }

    private function guardParent(?self $parent, int $levelsBelow): void
    {
        for ($ancestor = $parent; null !== $ancestor; $ancestor = $ancestor->parent) {
            if ($ancestor === $this) {
                throw new CatalogRuleViolation('Une catégorie ne peut pas être rangée sous elle-même ou sous une de ses sous-catégories.');
            }
        }
        $depth = (null === $parent ? 0 : $parent->depth()) + 1 + $levelsBelow;
        if ($depth > self::MAX_DEPTH) {
            throw new CatalogRuleViolation('Le catalogue a %max% niveaux au plus : cette place en donnerait %depth%.', ['%max%' => self::MAX_DEPTH, '%depth%' => $depth]);
        }
    }
}
