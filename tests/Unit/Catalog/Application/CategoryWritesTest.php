<?php

declare(strict_types=1);

namespace App\Tests\Unit\Catalog\Application;

use App\Catalog\Application\CategoryData;
use App\Catalog\Application\CreateCategory;
use App\Catalog\Application\DeleteCategory;
use App\Catalog\Application\UpdateCategory;
use App\Catalog\Domain\CatalogRuleViolation;
use App\Catalog\Domain\Category;
use App\Catalog\Domain\Exposure;
use App\Catalog\Domain\Price;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\Size;
use App\Tests\Support\InMemoryCategoryRepository;
use App\Tests\Support\InMemoryProductRepository;
use PHPUnit\Framework\TestCase;

final class CategoryWritesTest extends TestCase
{
    private InMemoryCategoryRepository $categories;
    private InMemoryProductRepository $products;
    private Category $plants;
    private Category $palms;

    protected function setUp(): void
    {
        $this->plants = new Category('plantes', 'Plantes', 1);
        $this->palms = new Category('palmiers', 'Palmiers', 1, $this->plants);
        $this->categories = new InMemoryCategoryRepository([$this->plants, $this->palms]);
        $this->products = new InMemoryProductRepository();
    }

    public function testCreatesACategoryUnderItsParent(): void
    {
        $created = new CreateCategory($this->categories)(self::data('nains', 'Nains', $this->palms->getId()));

        self::assertSame($this->palms, $created->getParent());
        self::assertSame($created, $this->categories->findBySlug('nains'));
    }

    public function testRefusesATakenSlug(): void
    {
        $this->expectExceptionObject(new CatalogRuleViolation('Le slug « palmiers » est déjà pris par une autre catégorie.'));
        new CreateCategory($this->categories)(self::data('palmiers', 'Doublon', null));
    }

    public function testRefusesAMissingParent(): void
    {
        $this->expectException(CatalogRuleViolation::class);
        new CreateCategory($this->categories)(self::data('orphelin', 'Orphelin', 999));
    }

    public function testKeepsItsOwnSlugOnUpdate(): void
    {
        new UpdateCategory($this->categories)((int) $this->palms->getId(), self::data('palmiers', 'Palmiers d\'intérieur', $this->plants->getId()));

        self::assertSame('Palmiers d\'intérieur', $this->palms->getName());
    }

    public function testRefusesAMoveThatWouldPushItsChildToAFourthLevel(): void
    {
        $trees = new Category('arbres', 'Arbres', 2);
        $fruitTrees = new Category('fruitiers', 'Fruitiers', 1, $trees);
        $this->categories->save($trees);
        $this->categories->save($fruitTrees);

        // « plantes » emporte « palmiers » : sous « fruitiers » (niveau 2), elle serait au niveau 3 et son enfant au niveau 4
        $this->expectExceptionObject(new CatalogRuleViolation('Le catalogue a 3 niveaux au plus : cette place en donnerait 4.'));
        new UpdateCategory($this->categories)((int) $this->plants->getId(), self::data('plantes', 'Plantes', $fruitTrees->getId()));
    }

    public function testRefusesToMoveACategoryUnderItsOwnChild(): void
    {
        $this->expectException(CatalogRuleViolation::class);
        new UpdateCategory($this->categories)((int) $this->plants->getId(), self::data('plantes', 'Plantes', $this->palms->getId()));
    }

    public function testRefusesToDeleteACategoryWithSubcategories(): void
    {
        $this->expectException(CatalogRuleViolation::class);
        new DeleteCategory($this->categories, $this->products)((int) $this->plants->getId());
    }

    public function testRefusesToDeleteACategoryWithProducts(): void
    {
        $this->products->save(new Product('palmier-nain', 'Palmier nain', $this->palms, Price::fromCents(4490), Exposure::Sun, Size::Medium, true, 1));

        $this->expectExceptionObject(new CatalogRuleViolation('« Palmiers » contient 1 produit(s) : les déplacer ou les supprimer d\'abord.'));
        new DeleteCategory($this->categories, $this->products)((int) $this->palms->getId());
    }

    public function testDeletesAnEmptyLeaf(): void
    {
        new DeleteCategory($this->categories, $this->products)((int) $this->palms->getId());

        self::assertNull($this->categories->findBySlug('palmiers'));
    }

    private static function data(string $slug, string $name, ?int $parentId): CategoryData
    {
        $data = new CategoryData();
        $data->slug = $slug;
        $data->name = $name;
        $data->position = 1;
        $data->parentId = $parentId;

        return $data;
    }
}
