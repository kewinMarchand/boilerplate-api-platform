<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\Catalog\Domain\CatalogRuleViolation;
use App\Catalog\Domain\CategoryRepository;
use App\Catalog\Domain\Price;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductRepository;

final readonly class CreateProduct
{
    public function __construct(
        private ProductRepository $products,
        private CategoryRepository $categories,
    ) {
    }

    /**
     * @throws CatalogRuleViolation      slug déjà pris, catégorie introuvable, visuel inconnu
     * @throws \InvalidArgumentException prix négatif
     */
    public function __invoke(ProductData $data): Product
    {
        if (null !== $this->products->findBySlug($data->slug)) {
            throw new CatalogRuleViolation(\sprintf('Le slug « %s » est déjà pris par un autre produit.', $data->slug));
        }
        $category = (null === $data->categoryId ? null : $this->categories->find($data->categoryId))
            ?? throw new CatalogRuleViolation('La catégorie du produit n\'existe pas.');

        $product = new Product($data->slug, $data->name, $category, Price::fromCents($data->price), $data->exposure, $data->size, $data->inStock, $data->image);
        $this->products->save($product);

        return $product;
    }
}
