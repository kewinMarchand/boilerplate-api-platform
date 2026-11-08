<?php

declare(strict_types=1);

namespace App\Tests\Unit\Site\Application;

use App\Media\Domain\Image;
use App\Media\Domain\MediaRuleViolation;
use App\Site\Application\GetSiteIdentity;
use App\Site\Application\SiteIdentityData;
use App\Site\Application\UpdateSiteIdentity;
use App\Site\Domain\DefaultColorScheme;
use App\Site\Domain\SiteIdentity;
use App\Site\Domain\SiteIdentityRepository;
use App\Site\Domain\SiteRuleViolation;
use App\Tests\Support\InMemoryMediaStorage;
use App\Tests\Support\TestImages;
use PHPUnit\Framework\TestCase;

final class SiteIdentityWritesTest extends TestCase
{
    public function testServesTheTemplateValuesUntilSomethingIsSaved(): void
    {
        $identities = $this->identities();

        self::assertSame('Boilerplate', new GetSiteIdentity($identities)()->getName());
        self::assertNull($identities->find());
    }

    public function testSavesTheIdentityAndANewLogoNamedAfterTheSite(): void
    {
        $identities = $this->identities();
        $data = $this->data();
        $data->imageContent = 'image/png 800x240';

        $this->update($identities)($data);

        $saved = $identities->find();
        self::assertNotNull($saved);
        self::assertSame(['Oraki', 'Plantes rares.', '#c2410c', '#ffffff', DefaultColorScheme::Dark], [
            $saved->getName(),
            $saved->getTagline(),
            $saved->getColors()?->primary->value,
            $saved->getColors()?->secondary->value,
            $saved->getDefaultColorScheme(),
        ]);
        self::assertSame(['Oraki', 120], [$saved->getLogo()?->alt, $saved->getLogo()?->height]);
    }

    public function testEmptyColorsGoBackToTheLibraryTokens(): void
    {
        $identities = $this->identities();
        $data = $this->data();
        $data->primaryColor = '';
        $data->secondaryColor = '';

        $this->update($identities)($data);

        self::assertNull($identities->find()?->getColors());
    }

    public function testKeepsTheCurrentLogoWithItsNewAlt(): void
    {
        $identities = $this->identities($this->withLogo());
        $data = $this->data();
        $data->imageAlt = 'Oraki, plantes rares';

        $this->update($identities)($data);

        self::assertEquals(new Image(str_repeat('b', 32), 'Oraki, plantes rares', 120), $identities->find()?->getLogo());
    }

    public function testRemovesTheLogoWithoutEncodingTheFileSentAlongside(): void
    {
        $identities = $this->identities($this->withLogo());
        $storage = new InMemoryMediaStorage();
        $data = $this->data();
        $data->removeLogo = true;
        $data->imageContent = 'image/png 800x240';

        $this->update($identities, $storage)($data);

        self::assertNull($identities->find()?->getLogo());
        self::assertSame([], $storage->files);
    }

    public function testRefusedColorsLeaveNoEncodedLogo(): void
    {
        $storage = new InMemoryMediaStorage();
        $data = $this->data();
        $data->secondaryColor = '';
        $data->imageContent = 'image/png 800x240';

        try {
            $this->update($this->identities(), $storage)($data);
            self::fail('La saisie aurait dû être refusée.');
        } catch (SiteRuleViolation $violation) {
            self::assertSame('Saisir les deux couleurs, ou aucune.', $violation->getMessage());
        }
        self::assertSame([], $storage->files);
    }

    public function testTheLibraryColorsBoxIgnoresTheColorsEntered(): void
    {
        $identities = $this->identities();
        $data = $this->data();
        $data->useLibraryColors = true;
        $data->primaryColor = '#ffff00';

        $this->update($identities)($data);

        self::assertNull($identities->find()?->getColors());
    }

    public function testSavesAnIconAndAShareImageNamedAfterTheSite(): void
    {
        $identities = $this->identities();
        $storage = new InMemoryMediaStorage();
        $data = $this->data();
        $data->iconContent = 'image/png 512x512';
        $data->shareImageContent = 'image/jpeg 1200x630';

        $this->update($identities, $storage)($data);

        $saved = $identities->find();
        self::assertSame(['', 'Oraki'], [$saved?->getIcon()?->alt, $saved?->getShareImage()?->alt]);
        self::assertArrayHasKey($saved?->getIcon()?->key.'-512.png', $storage->files);
        self::assertArrayHasKey($saved?->getShareImage()?->key.'-1200.webp', $storage->files);
    }

    public function testRemovesTheIconAndTheShareImageWithoutEncodingTheFilesSentAlongside(): void
    {
        $identity = SiteIdentity::byDefault();
        $identity->update('Oraki', '', null, null, DefaultColorScheme::Auto, new Image(str_repeat('c', 32), 'Oraki'), new Image(str_repeat('d', 32), ''));
        $identities = $this->identities($identity);
        $storage = new InMemoryMediaStorage();
        $data = $this->data();
        $data->removeIcon = true;
        $data->iconContent = 'image/png 512x512';
        $data->removeShareImage = true;
        $data->shareImageContent = 'image/jpeg 1200x630';

        $this->update($identities, $storage)($data);

        self::assertSame([null, null], [$identities->find()?->getIcon(), $identities->find()?->getShareImage()]);
        self::assertSame([], $storage->files);
    }

    public function testRefusesAnIconSmallerThan512Pixels(): void
    {
        $data = $this->data();
        $data->iconContent = 'image/png 256x256';

        $this->expectExceptionObject(new MediaRuleViolation('Recadrée au format 1:1, l\'image doit faire au moins 512 × 512 px : elle fait 256 × 256 px.'));
        $this->update($this->identities())($data);
    }

    private function withLogo(): SiteIdentity
    {
        $identity = SiteIdentity::byDefault();
        $identity->update('Oraki', '', new Image(str_repeat('b', 32), 'Oraki', 120), null, DefaultColorScheme::Auto, null, null);

        return $identity;
    }

    private function data(): SiteIdentityData
    {
        $data = new SiteIdentityData();
        $data->name = 'Oraki';
        $data->tagline = 'Plantes rares.';
        $data->useLibraryColors = false;
        $data->primaryColor = '#C2410C';
        $data->secondaryColor = '#ffffff';
        $data->defaultColorScheme = DefaultColorScheme::Dark;

        return $data;
    }

    private function update(SiteIdentityRepository $identities, ?InMemoryMediaStorage $storage = null): UpdateSiteIdentity
    {
        return new UpdateSiteIdentity(new GetSiteIdentity($identities), $identities, TestImages::changeImage($storage));
    }

    private function identities(?SiteIdentity $identity = null): SiteIdentityRepository
    {
        return new class($identity) implements SiteIdentityRepository {
            public function __construct(private ?SiteIdentity $identity)
            {
            }

            public function find(): ?SiteIdentity
            {
                return $this->identity;
            }

            public function save(SiteIdentity $identity): void
            {
                $this->identity = $identity;
            }
        };
    }
}
