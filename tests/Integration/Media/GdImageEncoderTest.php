<?php

declare(strict_types=1);

namespace App\Tests\Integration\Media;

use App\Media\Domain\ImageUsage;
use App\Media\Infrastructure\Gd\GdImageEncoder;
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

    public function testASlideIsCroppedToSixteenByNine(): void
    {
        $variants = iterator_to_array(new GdImageEncoder()->variants($this->jpegWithExif(2000, 2000), str_repeat('e', 32), ImageUsage::Slide));

        $size = getimagesizefromstring($variants[str_repeat('e', 32).'-1280.webp']);
        self::assertNotFalse($size);
        self::assertSame([1280, 720], [$size[0], $size[1]]);
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
