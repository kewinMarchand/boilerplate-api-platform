<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Catalog\Domain\ProductAttribute;
use App\Catalog\Domain\ProductAttributeRepository;

final class InMemoryProductAttributeRepository implements ProductAttributeRepository
{
    /** @var list<ProductAttribute> */
    private array $attributes = [];

    /**
     * @param list<ProductAttribute> $attributes enregistrés dans l'ordre, avec un identifiant attribué comme en base
     */
    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $attribute) {
            $this->save($attribute);
        }
    }

    public function findAll(): array
    {
        $attributes = $this->attributes;
        usort($attributes, static fn (ProductAttribute $a, ProductAttribute $b): int => $a->getPosition() <=> $b->getPosition());

        return $attributes;
    }

    public function find(int $id): ?ProductAttribute
    {
        foreach ($this->attributes as $attribute) {
            if ($attribute->getId() === $id) {
                return $attribute;
            }
        }

        return null;
    }

    public function findByCode(string $code): ?ProductAttribute
    {
        foreach ($this->attributes as $attribute) {
            if ($attribute->getCode() === $code) {
                return $attribute;
            }
        }

        return null;
    }

    public function save(ProductAttribute $attribute): void
    {
        if (null === $attribute->getId()) {
            Identity::assign($attribute, \count($this->attributes) + 1);
            $this->attributes[] = $attribute;
        }
    }

    public function remove(ProductAttribute $attribute): void
    {
        $this->attributes = array_values(array_filter($this->attributes, static fn (ProductAttribute $kept): bool => $kept !== $attribute));
    }
}
