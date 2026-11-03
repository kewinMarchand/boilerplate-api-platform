<?php

declare(strict_types=1);

namespace App\Content\Infrastructure\Doctrine;

use App\Content\Domain\LegalPage;
use App\Content\Domain\LegalPageRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineLegalPageRepository implements LegalPageRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function find(int $id): ?LegalPage
    {
        return $this->entityManager->find(LegalPage::class, $id);
    }

    public function findBySlug(string $slug): ?LegalPage
    {
        return $this->entityManager->getRepository(LegalPage::class)->findOneBy(['slug' => $slug]);
    }

    public function save(LegalPage $page): void
    {
        $this->entityManager->persist($page);
        $this->entityManager->flush();
    }
}
