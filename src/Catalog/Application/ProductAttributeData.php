<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\Catalog\Domain\AttributeChoice;
use App\Catalog\Domain\AttributeType;
use App\Catalog\Domain\CatalogRuleViolation;
use App\Catalog\Domain\ProductAttribute;

/**
 * Saisie d'un attribut de produit, remplie par l'admin. La forme (code, longueurs) est validée à la frontière.
 */
final class ProductAttributeData
{
    public string $code = '';
    public string $label = '';
    public AttributeType $type = AttributeType::Choice;
    public string $unit = '';
    public bool $filterable = false;
    public int $position = 0;
    /** @var list<AttributeChoiceData> dans l'ordre d'affichage */
    public array $choices = [];
    /** @var array<string, array<string, string|null>> traductions (ADR 0015), par langue puis par champ ; vide sur un site monolingue */
    public array $translations = [];

    public static function fromAttribute(ProductAttribute $attribute): self
    {
        $data = new self();
        $data->code = $attribute->getCode();
        $data->label = $attribute->getLabel();
        $data->type = $attribute->getType();
        $data->unit = $attribute->getUnit() ?? '';
        $data->filterable = $attribute->isFilterable();
        $data->position = $attribute->getPosition();
        foreach ($attribute->getChoices() as $choice) {
            $row = new AttributeChoiceData();
            $row->value = $choice->value;
            $row->label = $choice->label;
            foreach ($attribute->getTranslations() as $locale => $fields) {
                $row->labels[$locale] = $fields[ProductAttribute::CHOICE_LABEL.$choice->value] ?? '';
            }
            $data->choices[] = $row;
        }
        $data->translations = $attribute->getTranslations();

        return $data;
    }

    /**
     * Traductions de l'attribut avec les libellés traduits de ses valeurs (clés choice.<valeur>), qui remplacent ceux
     * d'avant.
     *
     * @return array<string, array<string, string|null>>
     */
    public function translationsWithChoices(): array
    {
        $translations = [];
        foreach ($this->translations as $locale => $fields) {
            $translations[$locale] = array_filter($fields, static fn (string $field): bool => !str_starts_with($field, ProductAttribute::CHOICE_LABEL), \ARRAY_FILTER_USE_KEY);
        }
        foreach ($this->choices as $row) {
            $value = trim($row->value);
            foreach ('' === $value ? [] : $row->labels as $locale => $label) {
                $translations[$locale][ProductAttribute::CHOICE_LABEL.$value] = $label;
            }
        }

        return $translations;
    }

    /**
     * @return list<AttributeChoice> lignes remplies, lignes vides écartées
     *
     * @throws CatalogRuleViolation ligne à moitié remplie
     */
    public function attributeChoices(): array
    {
        $choices = [];
        foreach ($this->choices as $row) {
            $value = trim($row->value);
            $label = trim($row->label);
            if ('' === $value && '' === $label) {
                continue;
            }
            if ('' === $value || '' === $label) {
                throw new CatalogRuleViolation('Chaque valeur a un code et un libellé.');
            }
            $choices[] = new AttributeChoice($value, $label);
        }

        return $choices;
    }
}
