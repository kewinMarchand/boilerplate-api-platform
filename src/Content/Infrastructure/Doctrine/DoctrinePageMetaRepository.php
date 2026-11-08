<?php

declare(strict_types=1);

namespace App\Content\Infrastructure\Doctrine;

use App\Content\Domain\PageMeta;
use App\Content\Domain\PageMetaRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrinePageMetaRepository implements PageMetaRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findAll(): array
    {
        return $this->entityManager->getRepository(PageMeta::class)->findAll();
    }

    public function find(int $id): ?PageMeta
    {
        return $this->entityManager->find(PageMeta::class, $id);
    }

    public function save(PageMeta $meta): void
    {
        $this->entityManager->persist($meta);
        $this->entityManager->flush();
    }
}
