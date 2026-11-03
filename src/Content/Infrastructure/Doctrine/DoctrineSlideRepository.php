<?php

declare(strict_types=1);

namespace App\Content\Infrastructure\Doctrine;

use App\Content\Domain\Slide;
use App\Content\Domain\SlideRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineSlideRepository implements SlideRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findInOrder(): array
    {
        return $this->entityManager->getRepository(Slide::class)->findBy([], ['position' => 'ASC', 'id' => 'ASC']);
    }

    public function find(int $id): ?Slide
    {
        return $this->entityManager->find(Slide::class, $id);
    }

    public function findBySlug(string $slug): ?Slide
    {
        return $this->entityManager->getRepository(Slide::class)->findOneBy(['slug' => $slug]);
    }

    public function save(Slide $slide): void
    {
        $this->entityManager->persist($slide);
        $this->entityManager->flush();
    }

    public function remove(Slide $slide): void
    {
        $this->entityManager->remove($slide);
        $this->entityManager->flush();
    }
}
