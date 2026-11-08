<?php

declare(strict_types=1);

namespace App\Tests\Unit\Site\Domain;

use App\Site\Domain\BrandColors;
use App\Site\Domain\DefaultColorScheme;
use App\Site\Domain\HexColor;
use App\Site\Domain\SiteIdentity;
use App\Tests\Support\TestImages;
use PHPUnit\Framework\TestCase;

final class SiteIdentityTest extends TestCase
{
    public function testDefaultsToTheTemplateValuesWithoutLogoNorColors(): void
    {
        $identity = SiteIdentity::byDefault();

        self::assertSame(['Boilerplate', null, null, DefaultColorScheme::Auto], [
            $identity->getName(),
            $identity->getLogo(),
            $identity->getColors(),
            $identity->getDefaultColorScheme(),
        ]);
    }

    public function testUpdatesEveryField(): void
    {
        $identity = SiteIdentity::byDefault();
        $identity->update('Oraki', 'Plantes rares.', TestImages::image('Oraki'), new BrandColors(new HexColor('#c2410c'), new HexColor('#ffffff')), DefaultColorScheme::Dark);

        self::assertSame(['Oraki', 'Plantes rares.', 'Oraki', '#c2410c', '#ffffff', DefaultColorScheme::Dark], [
            $identity->getName(),
            $identity->getTagline(),
            $identity->getLogo()?->alt,
            $identity->getColors()?->primary->value,
            $identity->getColors()?->secondary->value,
            $identity->getDefaultColorScheme(),
        ]);
    }
}
