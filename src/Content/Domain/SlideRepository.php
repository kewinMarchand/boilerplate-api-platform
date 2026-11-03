<?php

declare(strict_types=1);

namespace App\Content\Domain;

interface SlideRepository
{
    /**
     * @return list<Slide> triées par position
     */
    public function findInOrder(): array;

    public function find(int $id): ?Slide;

    public function findBySlug(string $slug): ?Slide;

    public function save(Slide $slide): void;

    public function remove(Slide $slide): void;
}
