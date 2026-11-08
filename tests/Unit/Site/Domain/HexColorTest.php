<?php

declare(strict_types=1);

namespace App\Tests\Unit\Site\Domain;

use App\Site\Domain\HexColor;
use App\Site\Domain\SiteRuleViolation;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class HexColorTest extends TestCase
{
    public function testIsStoredInLowercase(): void
    {
        self::assertSame('#0b1f3a', new HexColor(' #0B1F3A ')->value);
    }

    #[TestWith(['0b1f3a'])]
    #[TestWith(['#fff'])]
    #[TestWith(['#0b1f3a80'])]
    #[TestWith(['#gggggg'])]
    public function testRefusesAnythingButRrggbb(string $value): void
    {
        $this->expectExceptionObject(new SiteRuleViolation(\sprintf('Couleur invalide : « %s ». Format attendu : #rrggbb.', $value)));
        new HexColor($value);
    }

    public function testMixesLikeTheMockupCharts(): void
    {
        $primary = new HexColor('#0b1f3a');

        self::assertSame('#08172c', $primary->mix(new HexColor('#000000'), 0.25)->value);
        self::assertSame('#ebedef', new HexColor('#ffffff')->mix($primary, 0.08)->value);
    }

    public function testRoundsHalfToEvenLikePython(): void
    {
        // 6 × 0,75 = 4,5 : Python arrondit à 4, PHP par défaut à 5
        self::assertSame('#040404', new HexColor('#060606')->mix(new HexColor('#000000'), 0.25)->value);
    }

    #[TestWith(['#000000', '#ffffff', 21.0])]
    #[TestWith(['#1d4ed8', '#1d4ed8', 1.0])]
    #[TestWith(['#1d4ed8', '#ffffff', 6.70])]
    #[TestWith(['#ffffff', '#767676', 4.54])]
    public function testComputesTheWcagContrastRatio(string $first, string $second, float $expected): void
    {
        self::assertEqualsWithDelta($expected, new HexColor($first)->contrastWith(new HexColor($second)), 0.01);
        self::assertEqualsWithDelta($expected, new HexColor($second)->contrastWith(new HexColor($first)), 0.01);
    }
}
