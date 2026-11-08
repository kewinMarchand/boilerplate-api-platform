<?php

declare(strict_types=1);

namespace App\Tests\Unit\Site\Domain;

use App\Site\Domain\BrandPalette;
use App\Site\Domain\ColorShades;
use App\Site\Domain\HexColor;
use PHPUnit\Framework\TestCase;

/**
 * Valeurs attendues : clair, maquettes-sites/chartes.py (declinaisons, charte « rrc ») ; sombre, règles de la section 22
 * de SPEC-COMMUNE appliquées avec les fonctions de chartes.py, pour les couleurs de boilerplates-ui.
 */
final class BrandPaletteTest extends TestCase
{
    public function testDeclinesBothColorsOnTheLightBackground(): void
    {
        $palette = BrandPalette::light(new HexColor('#0B1F3A'), new HexColor('#F2B705'));

        self::assertSame(['#0b1f3a', '#08172c', '#ffffff', '#ebedef', '#d3d7dc', '#b1b7c0'], $this->values($palette->primary));
        self::assertSame(['#f2b705', '#b68904', '#111827', '#fef9eb', '#fdf2d2', '#fbe8af'], $this->values($palette->secondary));
    }

    public function testLightensEachColorInFivePercentStepsUntilSevenToOneOnTheDarkBackground(): void
    {
        $palette = BrandPalette::dark(new HexColor('#1d4ed8'), new HexColor('#7c3aed'));

        // 45 % de blanc pour les deux couleurs, -dark à 25 % de blanc en plus, texte de la couleur du fond
        self::assertSame(['#839eea', '#a2b6ef', '#0f141d', '#181f2d', '#1d2536', '#252e44'], $this->values($palette->primary));
        self::assertSame(['#b793f5', '#c9aef8', '#0f141d', '#1c1e2e', '#232337', '#2f2c46'], $this->values($palette->secondary));
        $background = new HexColor(BrandPalette::DARK_BACKGROUND);
        self::assertGreaterThanOrEqual(7.0, $palette->primary->color->contrastWith($background));
        self::assertLessThan(7.0, new HexColor('#1d4ed8')->mix(new HexColor('#ffffff'), 0.40)->contrastWith($background));
    }

    public function testTextOnADarkThemeColorIsAlwaysReadable(): void
    {
        foreach (['#000000', '#1d4ed8', '#7c3aed', '#9f66ae', '#ff5a1f'] as $color) {
            $shades = BrandPalette::dark(new HexColor($color), new HexColor($color))->primary;
            self::assertGreaterThanOrEqual(4.5, $shades->on->contrastWith($shades->color), $color);
        }
    }

    public function testKeepsAColorAlreadyReadableOnTheDarkBackground(): void
    {
        $palette = BrandPalette::dark(new HexColor('#f2b705'), new HexColor('#ffffff'));

        self::assertSame('#f2b705', $palette->primary->color->value);
        self::assertSame(['#ffffff', '#ffffff'], [$palette->secondary->color->value, $palette->secondary->dark->value]);
    }

    public function testLightensBlackToLightGray(): void
    {
        self::assertSame('#a6a6a6', BrandPalette::dark(new HexColor('#000000'), new HexColor('#000000'))->primary->color->value);
    }

    /**
     * @return list<string> couleur, foncée, texte dessus, surfaces légère, moyenne et forte
     */
    private function values(ColorShades $shades): array
    {
        return [$shades->color->value, $shades->dark->value, $shades->on->value, $shades->surfaceSubtle->value, $shades->surface->value, $shades->surfaceStrong->value];
    }
}
