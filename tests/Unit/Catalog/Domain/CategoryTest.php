<?php

declare(strict_types=1);

namespace App\Tests\Unit\Catalog\Domain;

use App\Catalog\Domain\CatalogRuleViolation;
use App\Catalog\Domain\Category;
use PHPUnit\Framework\TestCase;

final class CategoryTest extends TestCase
{
    public function testAcceptsThreeLevels(): void
    {
        $leaf = new Category('palmiers-nains', 'Palmiers nains', 1, new Category('palmiers', 'Palmiers', 1, new Category('plantes', 'Plantes', 1)));

        self::assertSame(3, $leaf->depth());
    }

    public function testRefusesAFourthLevel(): void
    {
        $third = new Category('c', 'C', 1, new Category('b', 'B', 1, new Category('a', 'A', 1)));

        $this->expectException(CatalogRuleViolation::class);
        new Category('d', 'D', 1, $third);
    }

    public function testRefusesAMoveThatPushesItsSubtreeBelowThreeLevels(): void
    {
        $root = new Category('a', 'A', 1);
        $moved = new Category('x', 'X', 1);

        $this->expectException(CatalogRuleViolation::class);
        $moved->update('x', 'X', 1, new Category('b', 'B', 1, $root), 1);
    }

    public function testRefusesToBecomeItsOwnAncestor(): void
    {
        $parent = new Category('a', 'A', 1);
        $child = new Category('b', 'B', 1, $parent);

        $this->expectException(CatalogRuleViolation::class);
        $parent->update('a', 'A', 1, $child, 1);
    }

    public function testUpdatesItsFields(): void
    {
        $category = new Category('a', 'A', 1);
        $parent = new Category('p', 'P', 1);

        $category->update('b', 'B', 2, $parent, 0);

        self::assertSame(['b', 'B', 2, $parent], [$category->getSlug(), $category->getName(), $category->getPosition(), $category->getParent()]);
    }
}
