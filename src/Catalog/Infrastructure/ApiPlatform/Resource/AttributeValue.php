<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductAttribute;

/**
 * Attribut renseigné sur un produit, avec ce qu'il faut pour l'afficher (section 23 de la spec commune).
 */
final readonly class AttributeValue
{
    public function __construct(
        #[ApiProperty(required: true)]
        public string $code,
        #[ApiProperty(required: true)]
        public string $label,
        #[ApiProperty(required: true, schema: ['type' => 'string', 'enum' => ['choice', 'number', 'text']])]
        public string $type,
        #[ApiProperty(description: 'Valeur en chaîne : code de la valeur pour choice, nombre ou texte sinon.', required: true)]
        public string $value,
        #[ApiProperty(description: 'Libellé de la valeur pour choice, la valeur elle-même sinon.', required: true)]
        public string $valueLabel,
        #[ApiProperty(description: 'Unité (« cm », « km »), null si l\'attribut n\'en a pas.', required: true)]
        public ?string $unit,
    ) {
    }

    /**
     * @param list<ProductAttribute> $attributes triés par position
     *
     * @return list<self> attributs renseignés sur le produit, par position
     */
    public static function listFor(Product $product, array $attributes): array
    {
        $values = [];
        foreach ($attributes as $attribute) {
            $value = $product->getAttributeValue($attribute->getCode());
            if (null !== $value) {
                $values[] = new self($attribute->getCode(), $attribute->getLabel(), $attribute->getType()->value, $value, $attribute->valueLabel($value), $attribute->getUnit());
            }
        }

        return $values;
    }
}
