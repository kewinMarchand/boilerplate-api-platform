<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Content\Domain\Slide;
use App\Content\Domain\SlideRepository;

final class InMemorySlideRepository implements SlideRepository
{
    /** @var list<Slide> */
    private array $slides = [];

    /**
     * @param list<Slide> $slides enregistrées dans l'ordre, avec un identifiant attribué comme en base
     */
    public function __construct(array $slides = [])
    {
        foreach ($slides as $slide) {
            $this->save($slide);
        }
    }

    public function findInOrder(): array
    {
        $slides = $this->slides;
        usort($slides, static fn (Slide $a, Slide $b): int => $a->getPosition() <=> $b->getPosition());

        return $slides;
    }

    public function find(int $id): ?Slide
    {
        foreach ($this->slides as $slide) {
            if ($slide->getId() === $id) {
                return $slide;
            }
        }

        return null;
    }

    public function findBySlug(string $slug): ?Slide
    {
        foreach ($this->slides as $slide) {
            if ($slide->getSlug() === $slug) {
                return $slide;
            }
        }

        return null;
    }

    public function save(Slide $slide): void
    {
        if (null === $slide->getId()) {
            Identity::assign($slide, \count($this->slides) + 1);
            $this->slides[] = $slide;
        }
    }

    public function remove(Slide $slide): void
    {
        $this->slides = array_values(array_filter($this->slides, static fn (Slide $kept): bool => $kept !== $slide));
    }
}
