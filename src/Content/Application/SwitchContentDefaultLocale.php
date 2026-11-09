<?php

declare(strict_types=1);

namespace App\Content\Application;

use App\Content\Domain\LegalPageRepository;
use App\Content\Domain\PageMetaRepository;

/**
 * Nouvelle langue par défaut du site (ADR 0015) : sur chaque page légale et page fixe, les traductions de la nouvelle langue passent dans
 * les colonnes et le texte actuel devient la traduction de l'ancienne. Appelé par l'admin avec la mise à jour de
 * l'identité, dans la même transaction.
 */
final readonly class SwitchContentDefaultLocale
{
    public function __construct(
        private LegalPageRepository $pages,
        private PageMetaRepository $metas,
    ) {
    }

    public function __invoke(string $from, string $to): void
    {
        foreach ($this->pages->findAll() as $page) {
            $page->switchDefaultLocale($from, $to);
            $this->pages->save($page);
        }
        foreach ($this->metas->findAll() as $meta) {
            $meta->switchDefaultLocale($from, $to);
            $this->metas->save($meta);
        }
    }
}
