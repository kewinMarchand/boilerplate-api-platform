<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * Unicité d'un slug dans chaque langue (ADR 0015). Dans une langue traduite, un élément a son slug traduit ou, à
 * défaut, celui de la langue par défaut : c'est ce slug-là qui ne doit appartenir à aucun autre élément de la langue.
 */
final class TranslatedSlugs
{
    /**
     * @param string                                    $slug         slug de la langue par défaut
     * @param array<string, array<string, string|null>> $translations saisie, par langue puis par champ
     * @param callable(string, ?string): ?object        $find         élément qui porte un slug dans une langue (null : la langue par défaut)
     * @param object|null                               $self         élément modifié, null à la création
     *
     * @return string|null le premier slug déjà pris par un autre élément, null s'il n'y en a pas
     */
    public static function taken(string $slug, array $translations, callable $find, ?object $self): ?string
    {
        $candidates = [[$slug, null]];
        foreach ($translations as $locale => $fields) {
            $translated = trim($fields['slug'] ?? '');
            $candidates[] = ['' === $translated ? $slug : $translated, $locale];
        }
        foreach ($candidates as [$candidate, $locale]) {
            $owner = $find($candidate, $locale);
            if (null !== $owner && $owner !== $self) {
                return $candidate;
            }
        }

        return null;
    }
}
