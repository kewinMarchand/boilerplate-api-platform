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

    public function testLightensEachColorInFivePercentStepsUntilFourAndAHalfToOneOnTheDarkSurface(): void
    {
        $palette = BrandPalette::dark(new HexColor('#1d4ed8'), new HexColor('#7c3aed'));

        // 35 % de blanc pour la principale, 30 % pour la secondaire, -dark à 25 % de blanc en plus, texte de la couleur du fond
        self::assertSame(['#6c8ce6', '#91a9ec', '#0f141d', '#161e2d', '#1a2235', '#212b43'], $this->values($palette->primary));
        self::assertSame(['#a375f2', '#ba98f5', '#0f141d', '#1b1c2e', '#212037', '#2b2645'], $this->values($palette->secondary));
        $surface = new HexColor(BrandPalette::DARK_CONTRAST_SURFACE);
        self::assertGreaterThanOrEqual(4.5, $palette->primary->color->contrastWith($surface));
        self::assertLessThan(4.5, new HexColor('#1d4ed8')->mix(new HexColor('#ffffff'), 0.30)->contrastWith($surface));
    }

    public function testKeepsTheBrandRedReadableOnASurfaceLighterThanTheBackground(): void
    {
        $palette = BrandPalette::dark(new HexColor('#e10600'), new HexColor('#cf4919'));

        self::assertSame(['#ec5d59', '#d96d47'], [$palette->primary->color->value, $palette->secondary->color->value]);
        self::assertGreaterThanOrEqual(4.5, $palette->primary->color->contrastWith(new HexColor('#1a1a1d')));
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

    public function testLightensBlackToMidGray(): void
    {
        self::assertSame('#8c8c8c', BrandPalette::dark(new HexColor('#000000'), new HexColor('#000000'))->primary->color->value);
    }

    /**
     * @return list<string> couleur, foncée, texte dessus, surfaces légère, moyenne et forte
     */
    private function values(ColorShades $shades): array
    {
        return [$shades->color->value, $shades->dark->value, $shades->on->value, $shades->surfaceSubtle->value, $shades->surface->value, $shades->surfaceStrong->value];
    }
}
