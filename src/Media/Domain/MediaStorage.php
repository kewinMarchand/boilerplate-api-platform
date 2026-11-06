<?php

declare(strict_types=1);

namespace App\Media\Domain;

interface MediaStorage
{
    public function has(string $fileName): bool;

    public function put(string $fileName, string $content): void;
}
