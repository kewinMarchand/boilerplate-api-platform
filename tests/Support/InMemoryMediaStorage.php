<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Media\Domain\MediaStorage;

final class InMemoryMediaStorage implements MediaStorage
{
    /** @var array<string, string> */
    public array $files = [];

    public function has(string $fileName): bool
    {
        return isset($this->files[$fileName]);
    }

    public function put(string $fileName, string $content): void
    {
        $this->files[$fileName] = $content;
    }

    public function putFile(string $fileName, string $sourcePath): void
    {
        $this->files[$fileName] = (string) file_get_contents($sourcePath);
    }
}
