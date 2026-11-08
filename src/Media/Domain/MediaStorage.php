<?php

declare(strict_types=1);

namespace App\Media\Domain;

interface MediaStorage
{
    public function has(string $fileName): bool;

    public function put(string $fileName, string $content): void;

    /**
     * Copie un fichier local (un fichier audio envoyé, trop lourd pour passer en mémoire).
     */
    public function putFile(string $fileName, string $sourcePath): void;
}
