<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\Catalog\Domain\CatalogRuleViolation;
use App\Catalog\Domain\ProductRepository;

final readonly class DeleteProduct
{
    public function __construct(private ProductRepository $products)
    {
    }

    /**
     * @throws CatalogRuleViolation produit introuvable
     */
    public function __invoke(int $id): void
    {
        $this->products->remove($this->products->find($id) ?? throw new CatalogRuleViolation('Ce produit n\'existe plus.'));
    }
}
