<?php

declare(strict_types=1);

namespace App\Tests\Unit\Media\Application;

use App\Media\Application\StoreAudio;
use App\Media\Domain\AudioFormat;
use App\Media\Domain\AudioInspector;
use App\Media\Domain\MediaRuleViolation;
use App\Media\Domain\SourceAudio;
use App\Tests\Support\InMemoryMediaStorage;
use PHPUnit\Framework\TestCase;

final class StoreAudioTest extends TestCase
{
    private InMemoryMediaStorage $storage;
    private string $path;

    protected function setUp(): void
    {
        $this->storage = new InMemoryMediaStorage();
        $this->path = (string) tempnam(sys_get_temp_dir(), 'audio');
        file_put_contents($this->path, 'son');
    }

    protected function tearDown(): void
    {
        @unlink($this->path);
    }

    public function testStoresTheFileUnderItsKeyWithItsRoundedDuration(): void
    {
        $audio = $this->storeAudio(new SourceAudio(AudioFormat::M4a, 1559.6))($this->path);

        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $audio->key);
        self::assertSame([AudioFormat::M4a, 1560], [$audio->format, $audio->durationSeconds]);
        self::assertSame([$audio->key.'.m4a' => 'son'], $this->storage->files);
    }

    public function testAVeryShortFileLastsOneSecond(): void
    {
        self::assertSame(1, $this->storeAudio(new SourceAudio(AudioFormat::Mp3, 0.2))($this->path)->durationSeconds);
    }

    public function testTheSameFileGetsTheSameKey(): void
    {
        $storeAudio = $this->storeAudio(new SourceAudio(AudioFormat::Mp3, 3));

        self::assertSame($storeAudio($this->path)->key, $storeAudio($this->path)->key);
    }

    public function testRefusesAFileThatIsNotAudio(): void
    {
        $this->expectExceptionObject(new MediaRuleViolation('Le fichier ne se lit pas comme un fichier audio MP3, M4A ou OGG.'));
        $this->storeAudio(null)($this->path);
    }

    public function testRefusesAFileOverOneHundredMegabytes(): void
    {
        $file = fopen($this->path, 'r+');
        self::assertNotFalse($file);
        ftruncate($file, StoreAudio::MAX_BYTES + 1);
        fclose($file);

        $this->expectExceptionObject(new MediaRuleViolation('Le fichier audio pèse plus de 100 Mo.'));
        $this->storeAudio(new SourceAudio(AudioFormat::Mp3, 3))($this->path);
    }

    private function storeAudio(?SourceAudio $source): StoreAudio
    {
        $inspector = new class($source) implements AudioInspector {
            public function __construct(private readonly ?SourceAudio $source)
            {
            }

            public function inspect(string $path): ?SourceAudio
            {
                return $this->source;
            }
        };

        return new StoreAudio($inspector, $this->storage);
    }
}
