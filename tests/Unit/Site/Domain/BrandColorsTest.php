<?php

declare(strict_types=1);

namespace App\Tests\Unit\Site\Domain;

use App\Site\Domain\BrandColors;
use App\Site\Domain\HexColor;
use App\Site\Domain\SiteRuleViolation;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class BrandColorsTest extends TestCase
{
    public function testNoColorAtAllKeepsTheLibraryTokens(): void
    {
        self::assertNull(BrandColors::fromInput(' ', ''));
    }

    #[TestWith(['#c2410c', ''])]
    #[TestWith(['', '#ffffff'])]
    public function testRefusesASingleColor(string $primary, string $secondary): void
    {
        $this->expectExceptionObject(new SiteRuleViolation('Saisir les deux couleurs, ou aucune.'));
        BrandColors::fromInput($primary, $secondary);
    }

    public function testRefusesAMalformedColor(): void
    {
        $this->expectExceptionObject(new SiteRuleViolation('Couleur invalide : « blanc ». Format attendu : #rrggbb.'));
        BrandColors::fromInput('#c2410c', 'blanc');
    }

    #[TestWith(['#ffff00', '1,07'])]
    #[TestWith(['#7a7a7a', '4,29'])]
    #[TestWith(['#777777', '4,47'])]
    public function testRefusesAPrimaryColorUnreadableOnTheLightBackground(string $primary, string $ratio): void
    {
        $this->expectExceptionObject(new SiteRuleViolation(\sprintf('La couleur principale doit contraster à 4,5:1 au moins avec le fond clair (actuellement %s:1).', $ratio)));
        new BrandColors(new HexColor($primary), new HexColor('#ffffff'));
    }

    public function testRefusesASecondaryColorThatCarriesNoReadableText(): void
    {
        $this->expectExceptionObject(new SiteRuleViolation('La couleur secondaire ne permet pas de texte lisible dessus (au mieux 4,21:1, 4,5:1 au minimum).'));
        new BrandColors(new HexColor('#c2410c'), new HexColor('#9f66ae'));
    }

    public function testAcceptsColorsAtTheThreshold(): void
    {
        $colors = BrandColors::fromInput('#767676', '#F2B705');

        self::assertSame(['#767676', '#f2b705'], [$colors?->primary->value, $colors?->secondary->value]);
    }
}
