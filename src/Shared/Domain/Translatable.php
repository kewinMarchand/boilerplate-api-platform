<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * Textes d'un agrégat dans les langues autres que la langue par défaut (SPEC-COMMUNE, section 29, ADR 0015) : colonne
 * JSON `translations`, {langue: {champ: valeur}}. Les colonnes de l'entité portent la langue par défaut. Un champ absent
 * dans la langue demandée prend la valeur de la langue par défaut (repli champ par champ). Les valeurs vides ne sont
 * pas gardées : vide veut dire « non traduit ».
 */
trait Translatable
{
    /** @var array<string, array<string, string>> colonne JSON, par langue puis par champ */
    private array $translations = [];

    /**
     * @return array<string, array<string, string>> par langue puis par champ, valeurs non vides
     */
    public function getTranslations(): array
    {
        return $this->translations;
    }

    /**
     * Remplace les traductions. Blancs de début et de fin retirés, fins de ligne ramenées à LF, valeurs vides écartées.
     *
     * @param array<string, array<string, string|null>> $translations par langue puis par champ
     */
    public function changeTranslations(array $translations): void
    {
        $kept = [];
        foreach ($translations as $locale => $fields) {
            foreach ($fields as $field => $value) {
                $value = trim(str_replace("\r\n", "\n", $value ?? ''));
                if ('' !== $value) {
                    $kept[$locale][$field] = $value;
                }
            }
        }
        $this->translations = $kept;
    }

    /**
     * Rend la langue $to langue par défaut : ses traductions passent dans les colonnes (champ par champ, la valeur
     * actuelle reste quand le champ n'est pas traduit), les valeurs actuelles des colonnes deviennent la traduction $from.
     */
    public function switchDefaultLocale(string $from, string $to): void
    {
        if ($from === $to) {
            return;
        }
        $current = $this->defaultTexts();
        $target = $this->translations[$to] ?? [];
        unset($this->translations[$to]);
        $this->translations[$from] = array_filter($current, static fn (string $value): bool => '' !== $value);
        $texts = [];
        foreach ($current as $field => $value) {
            $texts[$field] = $target[$field] ?? $value;
        }
        $this->applyDefaultTexts($texts);
    }

    /**
     * Valeur traduite d'un champ, ou celle de la langue par défaut. Locale null : la langue par défaut.
     */
    private function translated(string $field, string $default, ?string $locale): string
    {
        return null === $locale ? $default : ($this->translations[$locale][$field] ?? $default);
    }

    /**
     * Champs traduisibles et leur valeur dans la langue par défaut (colonnes de l'entité).
     *
     * @return array<string, string>
     */
    abstract private function defaultTexts(): array;

    /**
     * @param array<string, string> $texts mêmes clés que defaultTexts()
     */
    abstract private function applyDefaultTexts(array $texts): void;
}
