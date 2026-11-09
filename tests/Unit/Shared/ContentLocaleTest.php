<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared;

use App\Shared\Infrastructure\Locale\ContentLocale;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ContentLocaleTest extends TestCase
{
    #[DataProvider('cases')]
    public function testNegotiatesTheContentLanguage(mixed $parameter, ?string $acceptLanguage, string $expected): void
    {
        self::assertSame($expected, ContentLocale::negotiate(['fr', 'en'], $parameter, $acceptLanguage));
    }

    /**
     * @return iterable<string, array{mixed, ?string, string}>
     */
    public static function cases(): iterable
    {
        yield 'rien' => [null, null, 'fr'];
        yield 'en-tête vide' => [null, '', 'fr'];
        yield 'joker' => [null, '*', 'fr'];
        yield 'ordre d\'écriture à poids égal' => [null, 'en, fr', 'en'];
        yield 'poids' => [null, 'en;q=0.5, fr;q=0.9', 'fr'];
        yield 'poids invalide : 1' => [null, 'de, en;q=abc', 'en'];
        yield 'souligné' => [null, 'en_US', 'en'];
        yield 'paramètre tableau ignoré' => [['en'], 'fr', 'fr'];
        yield 'paramètre vide ignoré' => ['', 'en', 'en'];
    }

    public function testTheParameterOnlyNamesAnEnabledLanguage(): void
    {
        self::assertSame('fr', ContentLocale::negotiate(['fr'], 'en', 'en'));
    }
}
