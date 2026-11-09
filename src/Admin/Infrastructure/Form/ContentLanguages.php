<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Form;

/**
 * Langues que l'admin propose pour le contenu du site (ADR 0015), chacune écrite dans sa langue, comme dans les
 * sélecteurs de langue : le nom se lit sans dépendre de la langue de l'interface.
 */
final class ContentLanguages
{
    public const array NAMES = [
        'fr' => 'Français',
        'en' => 'English',
        'es' => 'Español',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'nl' => 'Nederlands',
        'pt' => 'Português',
    ];

    public static function name(string $locale): string
    {
        return self::NAMES[$locale] ?? $locale;
    }
}
