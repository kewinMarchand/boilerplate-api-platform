<?php

declare(strict_types=1);

namespace App\Tests\Unit\Catalog\Application;

use App\Catalog\Application\AttributeChoiceData;
use App\Catalog\Application\CreateProductAttribute;
use App\Catalog\Application\DeleteProductAttribute;
use App\Catalog\Application\ProductAttributeData;
use App\Catalog\Application\UpdateProductAttribute;
use App\Catalog\Domain\AttributeChoice;
use App\Catalog\Domain\AttributeType;
use App\Catalog\Domain\CatalogRuleViolation;
use App\Catalog\Domain\Category;
use App\Catalog\Domain\Exposure;
use App\Catalog\Domain\Price;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductAttribute;
use App\Catalog\Domain\Size;
use App\Tests\Support\InMemoryProductAttributeRepository;
use App\Tests\Support\InMemoryProductRepository;
use App\Tests\Support\TestImages;
use PHPUnit\Framework\TestCase;

final class ProductAttributeWritesTest extends TestCase
{
    private InMemoryProductAttributeRepository $attributes;
    private InMemoryProductRepository $products;
    private ProductAttribute $foliage;
    private Product $palm;

    protected function setUp(): void
    {
        $this->foliage = new ProductAttribute('feuillage', 'Feuillage', AttributeType::Choice, null, true, 1, [new AttributeChoice('persistant', 'Persistant'), new AttributeChoice('caduc', 'Caduc')]);
        $this->attributes = new InMemoryProductAttributeRepository([$this->foliage]);
        $palms = new Category('palmiers', 'Palmiers', 1);
        $this->palm = new Product('palmier-nain', 'Palmier nain', $palms, Price::fromCents(4490), Exposure::Sun, Size::Medium, true, TestImages::image());
        $this->palm->changeAttributeValues(['feuillage' => 'persistant', 'hauteur' => '250']);
        $this->products = new InMemoryProductRepository([$this->palm]);
    }

    public function testCreatesAnAttributeAndSkipsEmptyRows(): void
    {
        $data = $this->data('port', AttributeType::Choice, ['buissonnant' => 'Buissonnant', '' => '']);

        $created = new CreateProductAttribute($this->attributes)($data);

        self::assertSame($created, $this->attributes->findByCode('port'));
        self::assertEquals([new AttributeChoice('buissonnant', 'Buissonnant')], $created->getChoices());
    }

    public function testRefusesATakenCode(): void
    {
        $this->expectExceptionObject(new CatalogRuleViolation('Le code « feuillage » est déjà pris par un autre attribut.'));

        new CreateProductAttribute($this->attributes)($this->data('feuillage', AttributeType::Choice));
    }

    public function testRefusesAHalfFilledRow(): void
    {
        $data = $this->data('port', AttributeType::Choice);
        $row = new AttributeChoiceData();
        $row->value = 'buissonnant';
        $data->choices = [$row];

        $this->expectExceptionObject(new CatalogRuleViolation('Chaque valeur a un code et un libellé.'));
        new CreateProductAttribute($this->attributes)($data);
    }

    public function testUpdatesAnAttributeAndKeepsItsCode(): void
    {
        $data = $this->data('autre-code', AttributeType::Choice, ['persistant' => 'Toujours vert', 'semi-persistant' => 'Semi-persistant']);
        $data->label = 'Type de feuillage';

        $this->update($data);

        self::assertSame(['feuillage', 'Type de feuillage'], [$this->foliage->getCode(), $this->foliage->getLabel()]);
        self::assertSame('Toujours vert', $this->foliage->valueLabel('persistant'));
    }

    public function testRefusesToRemoveAValueCarriedByAProduct(): void
    {
        $this->expectExceptionObject(new CatalogRuleViolation('La valeur « Persistant » est utilisée par 1 produit.'));

        $this->update($this->data('feuillage', AttributeType::Choice, ['caduc' => 'Caduc']));
    }

    public function testRefusesToChangeTheTypeOfACarriedAttribute(): void
    {
        $this->expectExceptionObject(new CatalogRuleViolation('Le type de « Feuillage » ne peut plus changer : il est renseigné sur 1 produit.'));

        $this->update($this->data('feuillage', AttributeType::Text));
    }

    public function testDeletingAnAttributeRemovesItsValueFromTheProducts(): void
    {
        new DeleteProductAttribute($this->attributes, $this->products)((int) $this->foliage->getId());

        self::assertNull($this->attributes->findByCode('feuillage'));
        self::assertSame(['hauteur' => '250'], $this->palm->getAttributeValues());
    }

    private function update(ProductAttributeData $data): void
    {
        new UpdateProductAttribute($this->attributes, $this->products)((int) $this->foliage->getId(), $data);
    }

    /**
     * @param array<string, string> $choices code => libellé
     */
    private function data(string $code, AttributeType $type, array $choices = []): ProductAttributeData
    {
        $data = new ProductAttributeData();
        $data->code = $code;
        $data->label = ucfirst($code);
        $data->type = $type;
        $data->filterable = AttributeType::Choice === $type;
        foreach ($choices as $value => $label) {
            $row = new AttributeChoiceData();
            $row->value = (string) $value;
            $row->label = $label;
            $data->choices[] = $row;
        }

        return $data;
    }
}
