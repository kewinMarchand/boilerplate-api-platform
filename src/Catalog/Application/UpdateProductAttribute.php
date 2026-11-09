<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\Catalog\Domain\AttributeChoice;
use App\Catalog\Domain\CatalogRuleViolation;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductAttributeRepository;
use App\Catalog\Domain\ProductRepository;

/**
 * Le code ne change pas (les produits et les filtres le portent) : celui de la saisie est ignoré.
 */
final readonly class UpdateProductAttribute
{
    public function __construct(
        private ProductAttributeRepository $attributes,
        private ProductRepository $products,
    ) {
    }

    /**
     * @throws CatalogRuleViolation attribut introuvable, type changé ou valeur retirée alors que des produits les portent,
     *                              valeur en double ou incomplète, valeurs ou filtre hors liste
     */
    public function __invoke(int $id, ProductAttributeData $data): void
    {
        $attribute = $this->attributes->find($id) ?? throw new CatalogRuleViolation('Cet attribut n\'existe plus.');
        $code = $attribute->getCode();
        $carried = array_values(array_filter($this->products->findAll(), static fn (Product $product): bool => null !== $product->getAttributeValue($code)));

        if ($data->type !== $attribute->getType() && [] !== $carried) {
            throw new CatalogRuleViolation(1 === \count($carried) ? 'Le type de « %label% » ne peut plus changer : il est renseigné sur 1 produit.' : 'Le type de « %label% » ne peut plus changer : il est renseigné sur %count% produits.', ['%label%' => $attribute->getLabel(), '%count%' => \count($carried)]);
        }
        $choices = $data->attributeChoices();
        $kept = array_map(static fn (AttributeChoice $choice): string => $choice->value, $choices);
        foreach ($attribute->getChoices() as $choice) {
            if (\in_array($choice->value, $kept, true)) {
                continue;
            }
            $count = \count(array_filter($carried, static fn (Product $product): bool => $product->getAttributeValue($code) === $choice->value));
            if ($count > 0) {
                throw new CatalogRuleViolation(1 === $count ? 'La valeur « %label% » est utilisée par 1 produit.' : 'La valeur « %label% » est utilisée par %count% produits.', ['%label%' => $choice->label, '%count%' => $count]);
            }
        }

        $attribute->update($data->label, $data->type, $data->unit, $data->filterable, $data->position, $choices);
        $attribute->changeTranslations($data->translationsWithChoices());
        $this->attributes->save($attribute);
    }
}
