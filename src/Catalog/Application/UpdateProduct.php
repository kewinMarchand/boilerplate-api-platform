<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\Catalog\Domain\CatalogRuleViolation;
use App\Catalog\Domain\CategoryRepository;
use App\Catalog\Domain\Price;
use App\Catalog\Domain\ProductRepository;

final readonly class UpdateProduct
{
    public function __construct(
        private ProductRepository $products,
        private CategoryRepository $categories,
    ) {
    }

    /**
     * @throws CatalogRuleViolation      produit ou catégorie introuvable, slug déjà pris, visuel inconnu
     * @throws \InvalidArgumentException prix négatif
     */
    public function __invoke(int $id, ProductData $data): void
    {
        $product = $this->products->find($id) ?? throw new CatalogRuleViolation('Ce produit n\'existe plus.');
        $sameSlug = $this->products->findBySlug($data->slug);
        if (null !== $sameSlug && $sameSlug !== $product) {
            throw new CatalogRuleViolation(\sprintf('Le slug « %s » est déjà pris par un autre produit.', $data->slug));
        }
        $category = (null === $data->categoryId ? null : $this->categories->find($data->categoryId))
            ?? throw new CatalogRuleViolation('La catégorie du produit n\'existe pas.');

        $product->update($data->slug, $data->name, $category, Price::fromCents($data->price), $data->exposure, $data->size, $data->inStock, $data->image);
        $this->products->save($product);
    }
}
