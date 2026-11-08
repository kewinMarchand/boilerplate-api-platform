<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\Catalog\Domain\CategoryNotFound;
use App\Catalog\Domain\CategoryRepository;
use App\Catalog\Domain\CategoryTree;
use App\Catalog\Domain\ProductAttributeRepository;
use App\Catalog\Domain\ProductCriteria;
use App\Catalog\Domain\ProductRepository;
use App\Catalog\Domain\ProductSearch;
use App\Catalog\Domain\ProductSearchResult;

/**
 * Cas d'utilisation « rechercher des produits » : charge catalogue, arbre et attributs par les ports,
 * puis délègue les règles à ProductSearch.
 */
final readonly class SearchProducts
{
    public function __construct(
        private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository,
        private ProductAttributeRepository $attributeRepository,
        private ProductSearch $productSearch,
    ) {
    }

    /**
     * @throws CategoryNotFound si la catégorie demandée n'existe pas
     */
    public function __invoke(ProductCriteria $criteria): ProductSearchResult
    {
        return $this->productSearch->search(
            $this->productRepository->findAll(),
            new CategoryTree($this->categoryRepository->findAll()),
            $criteria,
            $this->attributeRepository->findAll(),
        );
    }
}
