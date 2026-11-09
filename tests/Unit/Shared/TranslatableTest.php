<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared;

use App\Catalog\Domain\Category;
use App\Shared\Domain\TranslatedSlugs;
use PHPUnit\Framework\TestCase;

final class TranslatableTest extends TestCase
{
    public function testFallsBackFieldByFieldOnTheDefaultLanguage(): void
    {
        $category = new Category('plantes', 'Plantes', 1);
        $category->changeTranslations(['en' => ['name' => ' Plants ', 'slug' => ''], 'de' => ['name' => null]]);

        self::assertSame('Plants', $category->getName('en'));
        self::assertSame('plantes', $category->getSlug('en'));
        self::assertSame('Plantes', $category->getName('de'));
        self::assertSame('Plantes', $category->getName());
        self::assertSame(['en' => ['name' => 'Plants']], $category->getTranslations());
    }

    public function testSwitchingTheDefaultLanguageMovesTheTexts(): void
    {
        $category = new Category('plantes', 'Plantes', 1);
        $category->changeTranslations(['en' => ['name' => 'Plants']]);

        $category->switchDefaultLocale('fr', 'en');

        self::assertSame('Plants', $category->getName());
        self::assertSame('plantes', $category->getSlug(), 'non traduit : le slug reste');
        self::assertSame('Plantes', $category->getName('fr'));
        self::assertSame(['fr' => ['name' => 'Plantes', 'slug' => 'plantes']], $category->getTranslations());
    }

    public function testASlugIsTakenInTheLanguageWhereTheElementWouldUseIt(): void
    {
        $other = new Category('fougeres', 'Fougères', 1);
        $other->changeTranslations(['en' => ['slug' => 'ferns']]);
        $self = new Category('palmiers', 'Palmiers', 2);
        $find = static fn (string $slug, ?string $locale): ?Category => array_find([$other, $self], static fn (Category $category): bool => $category->getSlug($locale) === $slug);

        self::assertNull(TranslatedSlugs::taken('palmiers', ['en' => ['slug' => 'palms']], $find, $self));
        self::assertSame('ferns', TranslatedSlugs::taken('palmiers', ['en' => ['slug' => 'ferns']], $find, $self));
        self::assertSame('fougeres', TranslatedSlugs::taken('fougeres', [], $find, $self));
        // Sans slug anglais, « ferns » sert en anglais : un élément qui l'a en slug français le prendrait
        self::assertSame('ferns', TranslatedSlugs::taken('ferns', ['en' => ['slug' => '']], $find, $self));
    }
}
