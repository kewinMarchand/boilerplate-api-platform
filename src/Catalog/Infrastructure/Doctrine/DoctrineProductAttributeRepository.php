<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Doctrine;

use App\Catalog\Domain\ProductAttribute;
use App\Catalog\Domain\ProductAttributeRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineProductAttributeRepository implements ProductAttributeRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findAll(): array
    {
        return $this->entityManager->getRepository(ProductAttribute::class)->findBy([], ['position' => 'ASC', 'id' => 'ASC']);
    }

    public function find(int $id): ?ProductAttribute
    {
        return $this->entityManager->find(ProductAttribute::class, $id);
    }

    public function findByCode(string $code): ?ProductAttribute
    {
        return $this->entityManager->getRepository(ProductAttribute::class)->findOneBy(['code' => $code]);
    }

    public function save(ProductAttribute $attribute): void
    {
        $this->entityManager->persist($attribute);
        $this->entityManager->flush();
    }

    public function remove(ProductAttribute $attribute): void
    {
        $this->entityManager->remove($attribute);
        $this->entityManager->flush();
    }
}
