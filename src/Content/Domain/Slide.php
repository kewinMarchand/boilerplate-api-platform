<?php

declare(strict_types=1);

namespace App\Content\Domain;

/**
 * Diapositive du carrousel de l'accueil. Son visuel est un des visuels livrés avec les fronts.
 * L'unicité du slug se vérifie dans les cas d'usage.
 */
class Slide
{
    public const int IMAGE_COUNT = 5;

    private ?int $id = null;

    /**
     * @param int $image index du visuel partagé, de 1 à 5
     */
    public function __construct(
        private string $slug,
        private string $title,
        private string $text,
        private int $image,
        private int $position,
    ) {
        self::guardImage($image);
    }

    /**
     * @throws ContentRuleViolation
     */
    public function update(string $slug, string $title, string $text, int $image, int $position): void
    {
        self::guardImage($image);
        $this->slug = $slug;
        $this->title = $title;
        $this->text = $text;
        $this->image = $image;
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

    public function getImage(): int
    {
        return $this->image;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    private static function guardImage(int $image): void
    {
        if ($image < 1 || $image > self::IMAGE_COUNT) {
            throw new ContentRuleViolation(\sprintf('Le visuel est un numéro de 1 à %d : %d.', self::IMAGE_COUNT, $image));
        }
    }
}
