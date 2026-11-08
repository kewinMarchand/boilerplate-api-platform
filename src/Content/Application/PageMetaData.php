<?php

declare(strict_types=1);

namespace App\Content\Application;

use App\Content\Domain\PageMeta;

/**
 * Saisie du référencement d'une page fixe, remplie par l'admin. Les longueurs sont validées à la frontière.
 */
final class PageMetaData
{
    public string $title = '';
    public string $description = '';

    public static function fromPageMeta(PageMeta $meta): self
    {
        $data = new self();
        $data->title = $meta->getTitle();
        $data->description = $meta->getDescription();

        return $data;
    }
}
