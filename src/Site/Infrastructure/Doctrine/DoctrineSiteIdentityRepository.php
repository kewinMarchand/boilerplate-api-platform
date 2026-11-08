<?php

declare(strict_types=1);

namespace App\Site\Infrastructure\Doctrine;

use App\Site\Domain\SiteIdentity;
use App\Site\Domain\SiteIdentityRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineSiteIdentityRepository implements SiteIdentityRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function find(): ?SiteIdentity
    {
        return $this->entityManager->find(SiteIdentity::class, SiteIdentity::ID);
    }

    public function save(SiteIdentity $identity): void
    {
        $this->entityManager->persist($identity);
        $this->entityManager->flush();
    }
}
