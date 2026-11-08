<?php

declare(strict_types=1);

namespace App\Media\Domain;

interface ImageEncoder
{
    /**
     * Type réel et dimensions du fichier, ou null s'il ne se lit pas comme une image.
     */
    public function inspect(string $content): ?SourceImage;

    /**
     * Recadre au ratio de l'usage, centré (sans recadrage pour un usage qui n'en a pas), puis encode chaque largeur dans chaque format de l'usage, sans métadonnées.
     *
     * @param non-empty-list<positive-int> $widths largeurs à générer (ImageUsage::widthsUpTo)
     *
     * @return iterable<string, string> nom du fichier de la variante => contenu encodé
     */
    public function variants(string $content, string $key, ImageUsage $usage, array $widths): iterable;
}
