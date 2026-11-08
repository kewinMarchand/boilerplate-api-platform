<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\Catalog\Domain\CatalogRuleViolation;
use App\Catalog\Domain\ProductAttribute;
use App\Catalog\Domain\ProductAttributeRepository;

final readonly class CreateProductAttribute
{
    public function __construct(private ProductAttributeRepository $attributes)
    {
    }

    /**
     * @throws CatalogRuleViolation code déjà pris, valeur en double ou incomplète, valeurs ou filtre hors liste
     */
    public function __invoke(ProductAttributeData $data): ProductAttribute
    {
        if (null !== $this->attributes->findByCode($data->code)) {
            throw new CatalogRuleViolation(\sprintf('Le code « %s » est déjà pris par un autre attribut.', $data->code));
        }

        $attribute = new ProductAttribute($data->code, $data->label, $data->type, $data->unit, $data->filterable, $data->position, $data->attributeChoices());
        $this->attributes->save($attribute);

        return $attribute;
    }
}
