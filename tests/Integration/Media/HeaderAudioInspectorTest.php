<?php

declare(strict_types=1);

namespace App\Tests\Integration\Media;

use App\Media\Domain\AudioFormat;
use App\Media\Infrastructure\Audio\HeaderAudioInspector;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

/**
 * Fichiers de tests/Fixtures/audio générés par ffmpeg (bip de 440 Hz) ; durées attendues : celles que lit ffprobe.
 */
final class HeaderAudioInspectorTest extends TestCase
{
    private const string DIRECTORY = __DIR__.'/../../Fixtures/audio/';

    #[TestWith(['beep-3s-cbr.mp3', AudioFormat::Mp3, 3.030])]
    #[TestWith(['beep-3s-vbr-id3.mp3', AudioFormat::Mp3, 3.030])]
    #[TestWith(['beep-3s.ogg', AudioFormat::Ogg, 3.0])]
    #[TestWith(['beep-3s.opus', AudioFormat::Ogg, 3.0])]
    #[TestWith(['beep-3s.m4a', AudioFormat::M4a, 3.0])]
    #[TestWith(['beep-3s-faststart.m4a', AudioFormat::M4a, 3.0])]
    public function testReadsTheFormatAndTheDurationFromTheHeaders(string $file, AudioFormat $format, float $duration): void
    {
        $source = new HeaderAudioInspector()->inspect(self::DIRECTORY.$file);

        self::assertNotNull($source);
        self::assertSame($format, $source->format);
        self::assertEqualsWithDelta($duration, $source->durationSeconds, 0.01);
    }

    public function testReadsTheDemoFileOfTheFixtures(): void
    {
        self::assertEqualsWithDelta(4.05, new HeaderAudioInspector()->inspect(__DIR__.'/../../../fixtures/audio/article-demo.mp3')?->durationSeconds, 0.01);
    }

    #[TestWith(['video-1s.mp4'])]
    #[TestWith(['../../../fixtures/images/blog-1.webp'])]
    #[TestWith(['../../../composer.json'])]
    #[TestWith(['absent.mp3'])]
    public function testRefusesAFileThatIsNotAnAcceptedAudioFile(string $file): void
    {
        self::assertNull(new HeaderAudioInspector()->inspect(self::DIRECTORY.$file));
    }
}
