<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\Catalog\Domain\CatalogRuleViolation;
use App\Catalog\Domain\CategoryRepository;
use App\Catalog\Domain\Price;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductRepository;
use App\Media\Application\ChangeImage;
use App\Media\Domain\ImageUsage;
use App\Media\Domain\MediaRuleViolation;

final readonly class CreateProduct
{
    public function __construct(
        private ProductRepository $products,
        private CategoryRepository $categories,
        private ChangeImage $changeImage,
    ) {
    }

    /**
     * @throws CatalogRuleViolation      slug déjà pris, catégorie introuvable, image absente
     * @throws MediaRuleViolation        image refusée
     * @throws \InvalidArgumentException prix négatif
     */
    public function __invoke(ProductData $data): Product
    {
        if (null !== $this->products->findBySlug($data->slug)) {
            throw new CatalogRuleViolation(\sprintf('Le slug « %s » est déjà pris par un autre produit.', $data->slug));
        }
        $category = (null === $data->categoryId ? null : $this->categories->find($data->categoryId))
            ?? throw new CatalogRuleViolation('La catégorie du produit n\'existe pas.');
        $alt = '' === trim($data->imageAlt) ? $data->name : $data->imageAlt;
        $image = ($this->changeImage)(null, $data->imageContent, $alt, $data->imageDecorative, ImageUsage::Product)
            ?? throw new CatalogRuleViolation('Un produit a besoin d\'une image.');

        $product = new Product($data->slug, $data->name, $category, Price::fromCents($data->price), $data->exposure, $data->size, $data->inStock, $image, $data->featured, $data->description);
        $this->products->save($product);

        return $product;
    }
}
