<?php

declare(strict_types=1);

namespace App\Tests\Unit\Catalog\Application;

use App\Catalog\Application\CreateProduct;
use App\Catalog\Application\DeleteProduct;
use App\Catalog\Application\GalleryImageData;
use App\Catalog\Application\ProductData;
use App\Catalog\Application\UpdateProduct;
use App\Catalog\Domain\AttributeChoice;
use App\Catalog\Domain\AttributeType;
use App\Catalog\Domain\CatalogRuleViolation;
use App\Catalog\Domain\Category;
use App\Catalog\Domain\Exposure;
use App\Catalog\Domain\Price;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductAttribute;
use App\Catalog\Domain\Size;
use App\Media\Domain\Image;
use App\Tests\Support\InMemoryCategoryRepository;
use App\Tests\Support\InMemoryMediaStorage;
use App\Tests\Support\InMemoryProductAttributeRepository;
use App\Tests\Support\InMemoryProductRepository;
use App\Tests\Support\TestImages;
use PHPUnit\Framework\TestCase;

final class ProductWritesTest extends TestCase
{
    private InMemoryCategoryRepository $categories;
    private InMemoryProductRepository $products;
    private InMemoryProductAttributeRepository $attributes;
    private Category $palms;
    private Product $dwarfPalm;

    protected function setUp(): void
    {
        $this->palms = new Category('palmiers', 'Palmiers', 1);
        $this->categories = new InMemoryCategoryRepository([$this->palms]);
        $this->dwarfPalm = new Product('palmier-nain', 'Palmier nain', $this->palms, Price::fromCents(4490), Exposure::Sun, Size::Medium, true, TestImages::image());
        $this->products = new InMemoryProductRepository([$this->dwarfPalm]);
        $this->attributes = new InMemoryProductAttributeRepository([
            new ProductAttribute('feuillage', 'Feuillage', AttributeType::Choice, null, true, 1, [new AttributeChoice('persistant', 'Persistant'), new AttributeChoice('caduc', 'Caduc')]),
            new ProductAttribute('hauteur-adulte', 'Hauteur adulte', AttributeType::Number, 'cm', false, 2),
            new ProductAttribute('origine', 'Origine', AttributeType::Text, null, false, 3),
        ]);
    }

    public function testCreatesAProduct(): void
    {
        $created = new CreateProduct($this->products, $this->categories, $this->attributes, TestImages::changeImage())($this->data('palmier-geant', $this->palms->getId(), 9900));

        self::assertSame(9900, $created->getPrice()->cents);
        self::assertSame($created, $this->products->findBySlug('palmier-geant'));
    }

    public function testRefusesATakenSlug(): void
    {
        $this->expectException(CatalogRuleViolation::class);
        new CreateProduct($this->products, $this->categories, $this->attributes, TestImages::changeImage())($this->data('palmier-nain', $this->palms->getId(), 100));
    }

    public function testRefusesAMissingCategory(): void
    {
        $this->expectExceptionObject(new CatalogRuleViolation('La catégorie du produit n\'existe pas.'));
        new CreateProduct($this->products, $this->categories, $this->attributes, TestImages::changeImage())($this->data('sans-categorie', null, 100));
    }

    public function testRefusesANegativePrice(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CreateProduct($this->products, $this->categories, $this->attributes, TestImages::changeImage())($this->data('negatif', $this->palms->getId(), -1));
    }

    public function testUpdatesAProductAndKeepsItsSlug(): void
    {
        new UpdateProduct($this->products, $this->categories, $this->attributes, TestImages::changeImage())((int) $this->dwarfPalm->getId(), $this->data('palmier-nain', $this->palms->getId(), 0));

        self::assertSame(0, $this->dwarfPalm->getPrice()->cents);
    }

    public function testUpdatesTheDescriptionWithUnixLineEndings(): void
    {
        $data = $this->data('palmier-nain', $this->palms->getId(), 4490);
        $data->description = "  Rustique.\r\n\r\nRésiste au vent.  ";

        new UpdateProduct($this->products, $this->categories, $this->attributes, TestImages::changeImage())((int) $this->dwarfPalm->getId(), $data);

        self::assertSame("Rustique.\n\nRésiste au vent.", $this->dwarfPalm->getDescription());
    }

    public function testAProductWithoutAltTakesItsName(): void
    {
        $data = $this->data('palmier-geant', $this->palms->getId(), 9900);
        $data->imageAlt = '';

        $created = new CreateProduct($this->products, $this->categories, $this->attributes, TestImages::changeImage())($data);

        self::assertSame('Palmier geant', $created->getImage()?->alt);
    }

    public function testRecordsTheFilledAttributesAndIgnoresUnknownCodes(): void
    {
        $data = $this->data('palmier-nain', $this->palms->getId(), 4490);
        $data->attributes = ['feuillage' => 'persistant', 'hauteur-adulte' => ' 250 ', 'origine' => '', 'inconnu' => 'x'];

        new UpdateProduct($this->products, $this->categories, $this->attributes, TestImages::changeImage())((int) $this->dwarfPalm->getId(), $data);

        self::assertSame(['feuillage' => 'persistant', 'hauteur-adulte' => '250'], $this->dwarfPalm->getAttributeValues());
    }

    public function testEmptiesAnAttributeLeftBlank(): void
    {
        $this->dwarfPalm->changeAttributeValues(['feuillage' => 'persistant']);
        $data = $this->data('palmier-nain', $this->palms->getId(), 4490);
        $data->attributes = ['feuillage' => null];

        new UpdateProduct($this->products, $this->categories, $this->attributes, TestImages::changeImage())((int) $this->dwarfPalm->getId(), $data);

        self::assertSame([], $this->dwarfPalm->getAttributeValues());
    }

    /**
     * @return iterable<string, array{array<string, string>, string}>
     */
    public static function refusedAttributeValues(): iterable
    {
        yield 'valeur inconnue' => [['feuillage' => 'epineux'], '« epineux » n\'est pas une valeur de l\'attribut « Feuillage ».'];
        yield 'nombre illisible' => [['hauteur-adulte' => 'deux mètres'], 'L\'attribut « Hauteur adulte » attend un nombre.'];
        yield 'texte trop long' => [['origine' => str_repeat('a', 121)], 'L\'attribut « Origine » a 120 caractères au plus.'];
    }

    /**
     * @param array<string, string> $attributes
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('refusedAttributeValues')]
    public function testRefusesAnAttributeValueItsDefinitionRejects(array $attributes, string $message): void
    {
        $data = $this->data('palmier-geant', $this->palms->getId(), 9900);
        $data->attributes = $attributes;

        $this->expectExceptionObject(new CatalogRuleViolation($message));
        new CreateProduct($this->products, $this->categories, $this->attributes, TestImages::changeImage())($data);
    }

    public function testAddsGalleryImagesOrderedByPositionNamedAfterTheProduct(): void
    {
        $storage = new InMemoryMediaStorage();
        $data = $this->data('palmier-nain', $this->palms->getId(), 4490);
        $data->gallery = [$this->galleryRow('image/jpeg 600x600', 2), $this->galleryRow('image/jpeg 1600x1600', 1, 'Vue de près'), new GalleryImageData()];

        new UpdateProduct($this->products, $this->categories, $this->attributes, TestImages::changeImage($storage))((int) $this->dwarfPalm->getId(), $data);

        $gallery = $this->dwarfPalm->getGallery();
        self::assertSame([['Vue de près', null], ['Palmier nain', 600]], array_map(static fn (Image $image): array => [$image->alt, $image->width], $gallery));
        self::assertArrayHasKey($gallery[1]->key.'-600.webp', $storage->files);
    }

    public function testRemovesAGalleryImageAndRenamesAnother(): void
    {
        $this->dwarfPalm->changeGallery([TestImages::image('A', str_repeat('a', 32)), TestImages::image('B', str_repeat('b', 32))]);
        $data = ProductData::fromProduct($this->dwarfPalm);
        $data->gallery[0]->remove = true;
        $data->gallery[1]->alt = 'B, revue';

        new UpdateProduct($this->products, $this->categories, $this->attributes, TestImages::changeImage())((int) $this->dwarfPalm->getId(), $data);

        self::assertEquals([TestImages::image('B, revue', str_repeat('b', 32))], $this->dwarfPalm->getGallery());
    }

    public function testRefusesAGalleryOfMoreThanEightImagesBeforeEncodingAnything(): void
    {
        $storage = new InMemoryMediaStorage();
        $data = $this->data('palmier-geant', $this->palms->getId(), 100);
        $data->gallery = array_map(fn (int $position): GalleryImageData => $this->galleryRow('image/jpeg 1600x1600', $position), range(1, 9));

        try {
            new CreateProduct($this->products, $this->categories, $this->attributes, TestImages::changeImage($storage))($data);
            self::fail('Neuf images acceptées.');
        } catch (CatalogRuleViolation $violation) {
            self::assertSame('La galerie compte 8 images au plus (9 demandées).', $violation->getMessage());
        }
        self::assertSame([], $storage->files);
    }

    public function testSavesTheMetadataOfTheProduct(): void
    {
        $data = $this->data('palmier-nain', $this->palms->getId(), 4490);
        $data->metaTitle = ' Palmier nain en pot ';
        $data->metaDescription = 'Rustique, compact.';

        new UpdateProduct($this->products, $this->categories, $this->attributes, TestImages::changeImage())((int) $this->dwarfPalm->getId(), $data);

        self::assertSame(['Palmier nain en pot', 'Rustique, compact.'], [$this->dwarfPalm->getMetaTitle(), $this->dwarfPalm->getMetaDescription()]);
    }

    public function testDeletesAProduct(): void
    {
        new DeleteProduct($this->products)((int) $this->dwarfPalm->getId());

        self::assertNull($this->products->findBySlug('palmier-nain'));
    }

    private function data(string $slug, ?int $categoryId, int $price): ProductData
    {
        $data = new ProductData();
        $data->slug = $slug;
        $data->name = ucfirst(str_replace('-', ' ', $slug));
        $data->categoryId = $categoryId;
        $data->imageContent = TestImages::PHOTO;
        $data->price = $price;

        return $data;
    }

    private function galleryRow(string $content, int $position, string $alt = ''): GalleryImageData
    {
        $row = new GalleryImageData();
        $row->content = $content;
        $row->position = $position;
        $row->alt = $alt;

        return $row;
    }
}
