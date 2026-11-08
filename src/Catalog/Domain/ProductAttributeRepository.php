<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

interface ProductAttributeRepository
{
    /**
     * @return list<ProductAttribute> tous les attributs, triés par position
     */
    public function findAll(): array;

    public function find(int $id): ?ProductAttribute;

    public function findByCode(string $code): ?ProductAttribute;

    public function save(ProductAttribute $attribute): void;

    public function remove(ProductAttribute $attribute): void;
}
