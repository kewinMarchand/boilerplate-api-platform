<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\Catalog\Domain\CatalogRuleViolation;
use App\Catalog\Domain\ProductAttributeRepository;
use App\Catalog\Domain\ProductRepository;

/**
 * Supprimer un attribut retire sa valeur des produits qui le renseignent.
 */
final readonly class DeleteProductAttribute
{
    public function __construct(
        private ProductAttributeRepository $attributes,
        private ProductRepository $products,
    ) {
    }

    /**
     * @throws CatalogRuleViolation attribut introuvable
     */
    public function __invoke(int $id): void
    {
        $attribute = $this->attributes->find($id) ?? throw new CatalogRuleViolation('Cet attribut n\'existe plus.');
        foreach ($this->products->findAll() as $product) {
            if (null !== $product->getAttributeValue($attribute->getCode())) {
                $product->removeAttributeValue($attribute->getCode());
                $this->products->save($product);
            }
        }

        $this->attributes->remove($attribute);
    }
}
