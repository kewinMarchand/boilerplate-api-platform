<?php

declare(strict_types=1);

namespace App\Content\Domain;

interface LegalPageRepository
{
    /**
     * @return list<LegalPage>
     */
    public function findAll(): array;

    public function find(int $id): ?LegalPage;

    public function findBySlug(string $slug): ?LegalPage;

    public function save(LegalPage $page): void;
}
