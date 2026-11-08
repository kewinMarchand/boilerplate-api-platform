<?php

declare(strict_types=1);

namespace App\Contact\Domain;

/**
 * Produit concerné par une demande de renseignement, vu du contact : son slug et son nom.
 */
final readonly class InquiredProduct
{
    public function __construct(
        public string $slug,
        public string $name,
    ) {
    }
}
