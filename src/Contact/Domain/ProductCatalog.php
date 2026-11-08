<?php

declare(strict_types=1);

namespace App\Contact\Domain;

interface ProductCatalog
{
    public function findBySlug(string $slug): ?InquiredProduct;
}
