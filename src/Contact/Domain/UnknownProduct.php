<?php

declare(strict_types=1);

namespace App\Contact\Domain;

/**
 * Le slug envoyé avec le message ne désigne aucun produit.
 */
final class UnknownProduct extends \DomainException
{
    public function __construct(public readonly string $slug)
    {
        parent::__construct(\sprintf('Aucun produit pour le slug « %s ».', $slug));
    }
}
