<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\EasyAdmin;

use App\Shared\Infrastructure\Locale\ContentLocale;

/**
 * Langues du contenu pour les formulaires traduisibles (ADR 0015) : options default_locale et locales de
 * TranslationFields. Une seule langue activée : aucune langue traduite, aucun onglet.
 */
final readonly class FormLocales
{
    public function __construct(private ContentLocale $contentLocale)
    {
    }

    /**
     * @return array{default_locale: string, locales: list<string>}
     */
    public function options(): array
    {
        $locales = $this->contentLocale->locales();

        return ['default_locale' => $locales[0], 'locales' => \array_slice($locales, 1)];
    }
}
