<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\Catalog\Domain\CategoryRepository;
use App\Catalog\Domain\ProductAttributeRepository;
use App\Catalog\Domain\ProductRepository;

/**
 * Nouvelle langue par défaut du site (ADR 0015) : sur chaque catégorie, produit et attribut, les traductions de la nouvelle langue passent dans
 * les colonnes et le texte actuel devient la traduction de l'ancienne. Appelé par l'admin avec la mise à jour de
 * l'identité, dans la même transaction.
 */
final readonly class SwitchCatalogDefaultLocale
{
    public function __construct(
        private CategoryRepository $categories,
        private ProductRepository $products,
        private ProductAttributeRepository $attributes,
    ) {
    }

    public function __invoke(string $from, string $to): void
    {
        foreach ($this->categories->findAll() as $category) {
            $category->switchDefaultLocale($from, $to);
            $this->categories->save($category);
        }
        foreach ($this->products->findAll() as $product) {
            $product->switchDefaultLocale($from, $to);
            $this->products->save($product);
        }
        foreach ($this->attributes->findAll() as $attribute) {
            $attribute->switchDefaultLocale($from, $to);
            $this->attributes->save($attribute);
        }
    }
}
