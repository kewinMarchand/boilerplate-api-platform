<?php

declare(strict_types=1);

namespace App\Content\Domain;

interface PageMetaRepository
{
    /**
     * @return list<PageMeta>
     */
    public function findAll(): array;

    public function find(int $id): ?PageMeta;

    public function save(PageMeta $meta): void;
}
