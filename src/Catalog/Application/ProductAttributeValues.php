<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\Catalog\Domain\CatalogRuleViolation;
use App\Catalog\Domain\ProductAttribute;

/**
 * Valeurs d'attributs saisies pour un produit, contrôlées par leur définition. Partagé par la création et la modification.
 */
final class ProductAttributeValues
{
    /**
     * @param list<ProductAttribute> $attributes
     * @param array<string, ?string> $raw        par code ; un code sans définition est ignoré
     *
     * @return array<string, string> valeurs renseignées, par code
     *
     * @throws CatalogRuleViolation
     */
    public static function check(array $attributes, array $raw): array
    {
        $values = [];
        foreach ($attributes as $attribute) {
            $value = $attribute->valueFor($raw[$attribute->getCode()] ?? null);
            if (null !== $value) {
                $values[$attribute->getCode()] = $value;
            }
        }

        return $values;
    }
}
