<?php

declare(strict_types=1);

namespace App\Tests\Unit\Media\Application;

use App\Media\Application\ImageUpload;
use App\Media\Application\StoreImage;
use App\Media\Domain\ImageUsage;
use App\Media\Domain\MediaRuleViolation;
use App\Tests\Support\FakeImageEncoder;
use App\Tests\Support\InMemoryMediaStorage;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class StoreImageTest extends TestCase
{
    private FakeImageEncoder $encoder;
    private InMemoryMediaStorage $storage;
    private StoreImage $storeImage;

    protected function setUp(): void
    {
        $this->encoder = new FakeImageEncoder();
        $this->storage = new InMemoryMediaStorage();
        $this->storeImage = new StoreImage($this->encoder, $this->storage);
    }

    public function testStoresEveryVariantOfTheUsage(): void
    {
        $image = ($this->storeImage)(new ImageUpload('image/jpeg 1600x900', ' Fleur rouge '), ImageUsage::Article);

        self::assertSame('Fleur rouge', $image->alt);
        self::assertSame(
            [$image->key.'-480.avif', $image->key.'-480.webp', $image->key.'-960.avif', $image->key.'-960.webp'],
            array_keys($this->storage->files),
        );
    }

    public function testTheSameContentForTheSameUsageIsEncodedOnce(): void
    {
        $first = ($this->storeImage)(new ImageUpload('image/png 800x800', 'A'), ImageUsage::Product);
        $second = ($this->storeImage)(new ImageUpload('image/png 800x800', 'B'), ImageUsage::Product);

        self::assertSame($first->key, $second->key);
        self::assertSame(1, $this->encoder->encodings);
    }

    public function testTheSameContentForAnotherUsageGetsAnotherKey(): void
    {
        $product = ($this->storeImage)(new ImageUpload('image/jpeg 1600x1600', 'A'), ImageUsage::Product);
        $article = ($this->storeImage)(new ImageUpload('image/jpeg 1600x1600', 'A'), ImageUsage::Article);

        self::assertNotSame($product->key, $article->key);
    }

    public function testADecorativeImageHasAnEmptyAlt(): void
    {
        $image = ($this->storeImage)(new ImageUpload('image/webp 960x540', 'Ignoré', decorative: true), ImageUsage::Article);

        self::assertTrue($image->isDecorative());
    }

    public function testRefusesAMissingAlt(): void
    {
        $this->expectExceptionObject(new MediaRuleViolation('Le texte de remplacement est obligatoire, sauf pour une image décorative.'));
        ($this->storeImage)(new ImageUpload('image/jpeg 1600x900', '  '), ImageUsage::Article);
    }

    public function testRefusesAFileOverTenMegabytes(): void
    {
        $this->expectExceptionObject(new MediaRuleViolation('L\'image pèse plus de 10 Mo.'));
        ($this->storeImage)(new ImageUpload('image/jpeg 1600x900'.str_repeat('x', StoreImage::MAX_BYTES), 'A'), ImageUsage::Article);
    }

    public function testRefusesAFileThatIsNotAnImage(): void
    {
        $this->expectExceptionObject(new MediaRuleViolation('Le fichier ne se lit pas comme une image.'));
        ($this->storeImage)(new ImageUpload('%PDF-1.7', 'A'), ImageUsage::Article);
    }

    public function testRefusesAnUnacceptedType(): void
    {
        $this->expectExceptionObject(new MediaRuleViolation('Formats acceptés : JPEG, PNG, WebP ou AVIF.'));
        ($this->storeImage)(new ImageUpload('image/gif 1600x900', 'A'), ImageUsage::Article);
    }

    #[TestWith(['image/jpeg 959x540'])]
    #[TestWith(['image/jpeg 2000x530'])]
    public function testRefusesAnImageTooSmallOnceCropped(string $content): void
    {
        $this->expectException(MediaRuleViolation::class);
        $this->expectExceptionMessage('Recadrée au format 16:9, l\'image doit faire au moins 960 × 540 px');
        ($this->storeImage)(new ImageUpload($content, 'A'), ImageUsage::Article);
    }

    public function testALogoKeepsTheRatioOfTheFile(): void
    {
        $image = ($this->storeImage)(new ImageUpload('image/png 1000x300', 'Oraki'), ImageUsage::Logo);

        self::assertSame(120, $image->height);
        self::assertSame([$image->key.'-200.avif', $image->key.'-200.webp', $image->key.'-400.avif', $image->key.'-400.webp'], array_keys($this->storage->files));
    }

    public function testRefusesALogoNarrowerThanItsLargestVariant(): void
    {
        $this->expectExceptionObject(new MediaRuleViolation('L\'image doit faire au moins 400 px de large : elle fait 399 × 2000 px.'));
        ($this->storeImage)(new ImageUpload('image/png 399x2000', 'Oraki'), ImageUsage::Logo);
    }

    public function testANarrowProductSourceIsNeverEnlarged(): void
    {
        $image = ($this->storeImage)(new ImageUpload('image/jpeg 600x900', 'Fleur'), ImageUsage::Product);

        self::assertSame(600, $image->width);
        self::assertSame([$image->key.'-400.avif', $image->key.'-400.webp', $image->key.'-600.avif', $image->key.'-600.webp'], array_keys($this->storage->files));
    }

    public function testAProductSourceBetweenTwoWidthsKeepsTheSmallerOnes(): void
    {
        $image = ($this->storeImage)(new ImageUpload('image/png 700x700', 'Fleur'), ImageUsage::Product);

        self::assertSame(700, $image->width);
        self::assertSame([$image->key.'-400.avif', $image->key.'-400.webp', $image->key.'-640.avif', $image->key.'-640.webp', $image->key.'-700.avif', $image->key.'-700.webp'], array_keys($this->storage->files));
    }

    public function testAWideEnoughSourceKeepsTheWidthsOfItsUsage(): void
    {
        self::assertNull(($this->storeImage)(new ImageUpload('image/png 800x800', 'Fleur'), ImageUsage::Product)->width);
    }

    public function testRefusesAProductSourceUnderFourHundredPixelsOnceCropped(): void
    {
        $this->expectExceptionObject(new MediaRuleViolation('Recadrée au format 1:1, l\'image doit faire au moins 400 × 400 px : elle fait 1600 × 399 px.'));
        ($this->storeImage)(new ImageUpload('image/jpeg 1600x399', 'A'), ImageUsage::Product);
    }

    public function testAnIconIsEncodedInPngOnly(): void
    {
        $image = ($this->storeImage)(new ImageUpload('image/png 512x512', '', decorative: true), ImageUsage::Icon);

        self::assertSame([$image->key.'-32.png', $image->key.'-180.png', $image->key.'-192.png', $image->key.'-512.png'], array_keys($this->storage->files));
    }

    public function testRefusesAnIconUnder512Pixels(): void
    {
        $this->expectExceptionObject(new MediaRuleViolation('Recadrée au format 1:1, l\'image doit faire au moins 512 × 512 px : elle fait 511 × 511 px.'));
        ($this->storeImage)(new ImageUpload('image/png 511x511', '', decorative: true), ImageUsage::Icon);
    }

    public function testAShareImageIsCroppedToTwelveHundredBySixHundredThirty(): void
    {
        $image = ($this->storeImage)(new ImageUpload('image/jpeg 1600x900', 'Oraki'), ImageUsage::Share);

        self::assertSame([$image->key.'-1200.avif', $image->key.'-1200.webp'], array_keys($this->storage->files));
    }
}
