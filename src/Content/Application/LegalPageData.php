<?php

declare(strict_types=1);

namespace App\Content\Application;

use App\Content\Domain\LegalPage;

/**
 * Saisie d'une page légale, remplie par l'admin. Le slug ne se modifie pas.
 */
final class LegalPageData
{
    public string $title = '';
    /** Markdown ; chaque titre de niveau 2 ouvre une section. */
    public string $body = '';

    public static function fromLegalPage(LegalPage $page): self
    {
        $data = new self();
        $data->title = $page->getTitle();
        $data->body = $page->getBody();

        return $data;
    }
}
