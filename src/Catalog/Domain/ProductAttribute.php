<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

use App\Shared\Domain\Translatable;

/**
 * Agrégat : caractéristique de produit définie dans l'admin (section 23 de la spec commune).
 * Il garde ses valeurs sans doublon, réserve les valeurs et le filtre au type « choice »,
 * et contrôle la valeur qu'un produit lui donne. Le code ne change pas après la création :
 * les produits et les filtres le portent. Son unicité, et les valeurs encore portées par
 * des produits, se vérifient dans les cas d'usage. Traduisibles (ADR 0015) : libellé, unité et libellés des valeurs
 * (clés `choice.<valeur>`).
 */
class ProductAttribute
{
    use Translatable;

    public const string CHOICE_LABEL = 'choice.';
    public const int TEXT_MAX_LENGTH = 120;

    private ?int $id = null;

    /** @var list<array{value: string, label: string}> colonne JSON, dans l'ordre d'affichage */
    private array $choices = [];

    /**
     * @param list<AttributeChoice> $choices
     *
     * @throws CatalogRuleViolation
     */
    public function __construct(
        private string $code,
        private string $label,
        private AttributeType $type,
        private ?string $unit,
        private bool $filterable,
        private int $position,
        array $choices = [],
    ) {
        $this->update($label, $type, $unit, $filterable, $position, $choices);
    }

    /**
     * @param list<AttributeChoice> $choices
     *
     * @throws CatalogRuleViolation valeurs en double, valeurs ou filtre sur un type autre que « choice »
     */
    public function update(string $label, AttributeType $type, ?string $unit, bool $filterable, int $position, array $choices): void
    {
        if (AttributeType::Choice !== $type && [] !== $choices) {
            throw new CatalogRuleViolation('Seul un attribut de type liste a des valeurs.');
        }
        if (AttributeType::Choice !== $type && $filterable) {
            throw new CatalogRuleViolation('Seul un attribut de type liste peut servir de filtre au catalogue.');
        }
        $seen = [];
        foreach ($choices as $choice) {
            if (isset($seen[$choice->value])) {
                throw new CatalogRuleViolation('La valeur « %value% » est en double.', ['%value%' => $choice->value]);
            }
            $seen[$choice->value] = true;
        }

        $this->label = $label;
        $this->type = $type;
        $this->unit = '' === $unit ? null : $unit;
        $this->filterable = $filterable;
        $this->position = $position;
        $this->choices = array_map(static fn (AttributeChoice $choice): array => ['value' => $choice->value, 'label' => $choice->label], $choices);
    }

    /**
     * Valeur saisie pour un produit, contrôlée et mise en forme ; null si elle est vide (attribut non renseigné).
     *
     * @throws CatalogRuleViolation valeur inconnue, nombre illisible, texte trop long
     */
    public function valueFor(?string $raw): ?string
    {
        $value = trim($raw ?? '');
        if ('' === $value) {
            return null;
        }

        return match ($this->type) {
            AttributeType::Choice => null !== $this->choiceLabel($value) ? $value
                : throw new CatalogRuleViolation('« %value% » n\'est pas une valeur de l\'attribut « %label% ».', ['%value%' => $value, '%label%' => $this->label]),
            AttributeType::Number => 1 === preg_match('/^-?\d+(\.\d+)?$/', $value) ? $value
                : throw new CatalogRuleViolation('L\'attribut « %label% » attend un nombre.', ['%label%' => $this->label]),
            AttributeType::Text => mb_strlen($value) <= self::TEXT_MAX_LENGTH ? $value
                : throw new CatalogRuleViolation('L\'attribut « %label% » a %max% caractères au plus.', ['%label%' => $this->label, '%max%' => self::TEXT_MAX_LENGTH]),
        };
    }

    /**
     * Libellé d'une valeur portée par un produit : celui de la valeur pour une liste, la valeur elle-même sinon.
     */
    public function valueLabel(string $value, ?string $locale = null): string
    {
        $label = $this->choiceLabel($value);

        return null === $label ? $value : $this->translated(self::CHOICE_LABEL.$value, $label, $locale);
    }

    /** Facette du catalogue : un attribut de type liste marqué filtrable. */
    public function isFacet(): bool
    {
        return AttributeType::Choice === $this->type && $this->filterable;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getLabel(?string $locale = null): string
    {
        return $this->translated('label', $this->label, $locale);
    }

    public function getType(): AttributeType
    {
        return $this->type;
    }

    public function getUnit(?string $locale = null): ?string
    {
        $unit = $this->translated('unit', $this->unit ?? '', $locale);

        return '' === $unit ? null : $unit;
    }

    public function isFilterable(): bool
    {
        return $this->filterable;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @return list<AttributeChoice> dans l'ordre d'affichage
     */
    public function getChoices(?string $locale = null): array
    {
        return array_map(fn (array $choice): AttributeChoice => new AttributeChoice($choice['value'], $this->translated(self::CHOICE_LABEL.$choice['value'], $choice['label'], $locale)), $this->choices);
    }

    private function defaultTexts(): array
    {
        $texts = ['label' => $this->label, 'unit' => $this->unit ?? ''];
        foreach ($this->choices as $choice) {
            $texts[self::CHOICE_LABEL.$choice['value']] = $choice['label'];
        }

        return $texts;
    }

    private function applyDefaultTexts(array $texts): void
    {
        $this->label = $texts['label'];
        $this->unit = '' === $texts['unit'] ? null : $texts['unit'];
        foreach ($this->choices as $index => $choice) {
            $this->choices[$index]['label'] = $texts[self::CHOICE_LABEL.$choice['value']];
        }
    }

    private function choiceLabel(string $value): ?string
    {
        foreach ($this->choices as $choice) {
            if ($choice['value'] === $value) {
                return $choice['label'];
            }
        }

        return null;
    }
}
