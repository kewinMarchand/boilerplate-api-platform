<?php

declare(strict_types=1);

namespace App\Tests\Unit\Catalog\Domain;

use App\Catalog\Domain\AttributeChoice;
use App\Catalog\Domain\AttributeType;
use App\Catalog\Domain\CatalogRuleViolation;
use App\Catalog\Domain\ProductAttribute;
use PHPUnit\Framework\TestCase;

final class ProductAttributeTest extends TestCase
{
    public function testKeepsItsChoicesInOrderAndLabelsAValue(): void
    {
        $attribute = $this->foliage();

        self::assertSame(['persistant', 'caduc'], array_map(static fn (AttributeChoice $choice): string => $choice->value, $attribute->getChoices()));
        self::assertSame('Caduc', $attribute->valueLabel('caduc'));
        self::assertTrue($attribute->isFacet());
    }

    public function testRefusesADuplicateChoice(): void
    {
        $this->expectExceptionObject(new CatalogRuleViolation('La valeur « caduc » est en double.'));

        new ProductAttribute('feuillage', 'Feuillage', AttributeType::Choice, null, true, 1, [new AttributeChoice('caduc', 'Caduc'), new AttributeChoice('caduc', 'Caduque')]);
    }

    public function testRefusesChoicesOnAnotherType(): void
    {
        $this->expectExceptionObject(new CatalogRuleViolation('Seul un attribut de type liste a des valeurs.'));

        new ProductAttribute('hauteur', 'Hauteur', AttributeType::Number, 'cm', false, 1, [new AttributeChoice('grand', 'Grand')]);
    }

    public function testRefusesAFilterOnAnotherType(): void
    {
        $this->expectExceptionObject(new CatalogRuleViolation('Seul un attribut de type liste peut servir de filtre au catalogue.'));

        new ProductAttribute('origine', 'Origine', AttributeType::Text, null, true, 1);
    }

    public function testAnEmptyUnitIsNoUnit(): void
    {
        self::assertNull(new ProductAttribute('origine', 'Origine', AttributeType::Text, '', false, 1)->getUnit());
    }

    public function testChecksTheValueOfAProduct(): void
    {
        $height = new ProductAttribute('hauteur', 'Hauteur', AttributeType::Number, 'cm', false, 1);

        self::assertSame('persistant', $this->foliage()->valueFor('persistant'));
        self::assertSame('12.5', $height->valueFor(' 12.5 '));
        self::assertSame('12.5', $height->valueLabel('12.5'));
        self::assertNull($height->valueFor(''));
        self::assertNull($height->valueFor(null));
    }

    public function testRefusesAValueOutsideTheChoices(): void
    {
        $this->expectExceptionObject(new CatalogRuleViolation('« epineux » n\'est pas une valeur de l\'attribut « Feuillage ».'));

        $this->foliage()->valueFor('epineux');
    }

    private function foliage(): ProductAttribute
    {
        return new ProductAttribute('feuillage', 'Feuillage', AttributeType::Choice, null, true, 1, [new AttributeChoice('persistant', 'Persistant'), new AttributeChoice('caduc', 'Caduc')]);
    }
}
