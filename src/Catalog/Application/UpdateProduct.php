<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\Catalog\Domain\CatalogRuleViolation;
use App\Catalog\Domain\CategoryRepository;
use App\Catalog\Domain\Price;
use App\Catalog\Domain\ProductAttributeRepository;
use App\Catalog\Domain\ProductRepository;
use App\Media\Application\ChangeImage;
use App\Media\Domain\ImageUsage;
use App\Media\Domain\MediaRuleViolation;

final readonly class UpdateProduct
{
    public function __construct(
        private ProductRepository $products,
        private CategoryRepository $categories,
        private ProductAttributeRepository $attributes,
        private ChangeImage $changeImage,
    ) {
    }

    /**
     * @throws CatalogRuleViolation      produit ou catégorie introuvable, slug déjà pris, image absente, valeur d'attribut refusée, plus de 8 images dans la galerie
     * @throws MediaRuleViolation        image ou image de la galerie refusée
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
        $attributeValues = ProductAttributeValues::check($this->attributes->findAll(), $data->attributes);
        $gallery = ProductGallery::change($this->changeImage, $data->gallery, $data->name);
        $alt = '' === trim($data->imageAlt) ? $data->name : $data->imageAlt;
        $image = ($this->changeImage)($product->getImage(), $data->imageContent, $alt, $data->imageDecorative, ImageUsage::Product)
            ?? throw new CatalogRuleViolation('Un produit a besoin d\'une image.');

        $product->update($data->slug, $data->name, $category, Price::fromCents($data->price), $data->exposure, $data->size, $data->inStock, $image, $data->featured, $data->description);
        $product->changeAttributeValues($attributeValues);
        $product->changeGallery($gallery);
        $product->changeMeta($data->metaTitle, $data->metaDescription);
        $this->products->save($product);
    }
}
