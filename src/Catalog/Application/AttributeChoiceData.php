<?php

declare(strict_types=1);

namespace App\Catalog\Application;

/**
 * Ligne de valeur d'un attribut à choix, remplie par l'admin. Une ligne vide est ignorée.
 */
final class AttributeChoiceData
{
    public string $value = '';
    public string $label = '';
    /** @var array<string, string|null> libellé dans chaque langue traduite (ADR 0015) ; vide : celui de la langue par défaut */
    public array $labels = [];
}
