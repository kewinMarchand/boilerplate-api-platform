<?php

declare(strict_types=1);

namespace App\Site\Domain;

/**
 * Couleur opaque #rrggbb, en minuscules. Mélange et contraste suivent maquettes-sites/chartes.py (melange, luminance,
 * contraste), arrondi au pair compris, pour que l'API et les maquettes donnent les mêmes valeurs.
 */
final readonly class HexColor
{
    private const string PATTERN = '/^#[0-9a-f]{6}$/';

    public string $value;

    /**
     * @throws SiteRuleViolation
     */
    public function __construct(string $value)
    {
        $value = strtolower(trim($value));
        if (1 !== preg_match(self::PATTERN, $value)) {
            throw new SiteRuleViolation(\sprintf('Couleur invalide : « %s ». Format attendu : #rrggbb.', $value));
        }
        $this->value = $value;
    }

    /**
     * @param float $otherPart part de $other dans le mélange, de 0 à 1
     */
    public function mix(self $other, float $otherPart): self
    {
        $channels = array_map(
            static fn (int $own, int $theirs): int => (int) max(0, min(255, round($own * (1 - $otherPart) + $theirs * $otherPart, 0, \PHP_ROUND_HALF_EVEN))),
            $this->channels(),
            $other->channels(),
        );

        return new self(\sprintf('#%02x%02x%02x', ...$channels));
    }

    /**
     * Rapport de contraste WCAG, de 1 à 21.
     */
    public function contrastWith(self $other): float
    {
        $lighter = max($this->luminance(), $other->luminance());
        $darker = min($this->luminance(), $other->luminance());

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    private function luminance(): float
    {
        [$red, $green, $blue] = array_map(
            static function (int $channel): float {
                $value = $channel / 255;

                return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
            },
            $this->channels(),
        );

        return 0.2126 * $red + 0.7152 * $green + 0.0722 * $blue;
    }

    /**
     * @return list<int> rouge, vert, bleu
     */
    private function channels(): array
    {
        return array_map(static fn (string $pair): int => (int) hexdec($pair), str_split(substr($this->value, 1), 2));
    }
}
