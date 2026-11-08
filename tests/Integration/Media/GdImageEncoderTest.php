<?php

declare(strict_types=1);

namespace App\Tests\Integration\Media;

use App\Media\Application\ImageUpload;
use App\Media\Application\StoreImage;
use App\Media\Domain\ImageUsage;
use App\Media\Domain\MediaRuleViolation;
use App\Media\Infrastructure\Gd\GdImageEncoder;
use App\Tests\Support\InMemoryMediaStorage;
use PHPUnit\Framework\TestCase;

/**
 * Encodeur réel (extensions gd et exif de l'image Docker).
 */
final class GdImageEncoderTest extends TestCase
{
    private const string WITNESS = 'TEMOIN-GPS-48.8566N';

    public function testInspectAppliesTheExifOrientation(): void
    {
        $source = new GdImageEncoder()->inspect($this->jpegWithExif(1600, 900, orientation: 6));

        self::assertSame(['image/jpeg', 900, 1600], [$source?->mimeType, $source?->width, $source?->height]);
    }

    public function testInspectRejectsAFileThatIsNotAnImage(): void
    {
        self::assertNull(new GdImageEncoder()->inspect('%PDF-1.7'));
    }

    public function testVariantsAreCroppedResizedAndStrippedOfMetadata(): void
    {
        $key = str_repeat('f', 32);
        $variants = iterator_to_array(new GdImageEncoder()->variants($this->jpegWithExif(1600, 900, orientation: 6), $key, ImageUsage::Product));

        self::assertSame([$key.'-400.avif', $key.'-400.webp', $key.'-640.avif', $key.'-640.webp', $key.'-800.avif', $key.'-800.webp'], array_keys($variants));
        foreach ($variants as $name => $content) {
            $size = getimagesizefromstring($content);
            $width = (int) preg_replace('/^.*-(\d+)\..*$/', '$1', $name);
            self::assertNotFalse($size);
            self::assertSame([$width, $width], [$size[0], $size[1]], $name);
            self::assertSame(str_ends_with($name, '.avif') ? 'image/avif' : 'image/webp', $size['mime'], $name);
            self::assertStringNotContainsString(self::WITNESS, $content, $name);
            self::assertStringNotContainsString('Exif', $content, $name);
        }
    }

    public function testAnArticleImageIsCroppedToSixteenByNine(): void
    {
        $variants = iterator_to_array(new GdImageEncoder()->variants($this->jpegWithExif(2000, 2000), str_repeat('e', 32), ImageUsage::Article));

        $size = getimagesizefromstring($variants[str_repeat('e', 32).'-960.webp']);
        self::assertNotFalse($size);
        self::assertSame([960, 540], [$size[0], $size[1]]);
    }

    public function testALogoKeepsItsRatioAndItsTransparency(): void
    {
        $logo = imagecreatetruecolor(1000, 300);
        self::assertNotFalse($logo);
        imagealphablending($logo, false);
        imagesavealpha($logo, true);
        imagefill($logo, 0, 0, (int) imagecolorallocatealpha($logo, 0, 0, 0, 127));
        imagefilledrectangle($logo, 400, 100, 600, 200, (int) imagecolorallocatealpha($logo, 194, 65, 12, 0));
        ob_start();
        imagepng($logo);
        $key = str_repeat('d', 32);

        $variants = iterator_to_array(new GdImageEncoder()->variants((string) ob_get_clean(), $key, ImageUsage::Logo));

        self::assertSame([$key.'-200.avif', $key.'-200.webp', $key.'-400.avif', $key.'-400.webp'], array_keys($variants));
        foreach (['200.webp' => [200, 60], '400.webp' => [400, 120], '400.avif' => [400, 120]] as $suffix => $dimensions) {
            $size = getimagesizefromstring($variants[$key.'-'.$suffix]);
            self::assertNotFalse($size);
            self::assertSame($dimensions, [$size[0], $size[1]], $suffix);
        }
        $webp = imagecreatefromstring($variants[$key.'-400.webp']);
        self::assertNotFalse($webp);
        self::assertSame(127, (imagecolorat($webp, 0, 0) >> 24) & 0x7F, 'coin transparent');
        self::assertSame(0, (imagecolorat($webp, 200, 60) >> 24) & 0x7F, 'centre opaque');
    }

    public function testAnSvgLogoIsRefused(): void
    {
        // PHP 8.5 lit les dimensions d'un SVG : c'est la liste des types acceptés qui le refuse
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="120"><rect width="400" height="120"/></svg>';

        $this->expectExceptionObject(new MediaRuleViolation('Formats acceptés : JPEG, PNG, WebP ou AVIF.'));
        new StoreImage(new GdImageEncoder(), new InMemoryMediaStorage())(new ImageUpload($svg, 'Oraki'), ImageUsage::Logo);
    }

    /**
     * JPEG avec un segment EXIF : orientation, et une description qui sert de témoin des métadonnées.
     *
     * @param positive-int $width
     * @param positive-int $height
     */
    private function jpegWithExif(int $width, int $height, int $orientation = 1): string
    {
        $image = imagecreatetruecolor($width, $height);
        self::assertNotFalse($image);
        imagefilledrectangle($image, 0, 0, intdiv($width, 2), $height, (int) imagecolorallocate($image, 200, 30, 30));
        ob_start();
        imagejpeg($image, null, 85);
        $jpeg = (string) ob_get_clean();

        $text = self::WITNESS."\0";
        $dataOffset = 8 + 2 + 2 * 12 + 4;
        $tiff = 'II*'."\0".pack('V', 8)
            .pack('v', 2)
            .pack('vvVV', 0x010E, 2, \strlen($text), $dataOffset)
            .pack('vvVvv', 0x0112, 3, 1, $orientation, 0)
            .pack('V', 0)
            .$text;
        $app1 = "Exif\0\0".$tiff;

        return substr($jpeg, 0, 2)."\xFF\xE1".pack('n', \strlen($app1) + 2).$app1.substr($jpeg, 2);
    }
}
