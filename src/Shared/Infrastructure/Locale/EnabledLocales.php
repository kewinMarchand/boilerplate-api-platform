<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Locale;

/**
 * Langues activées du site, la langue par défaut en premier (ADR 0015). Implémenté par le module Site.
 */
interface EnabledLocales
{
    /**
     * @return non-empty-list<string>
     */
    public function enabled(): array;
}
