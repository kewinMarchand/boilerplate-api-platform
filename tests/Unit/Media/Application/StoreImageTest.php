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
}
