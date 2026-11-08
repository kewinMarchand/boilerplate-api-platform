<?php

declare(strict_types=1);

namespace App\Media\Domain;

/**
 * Usage d'une image envoyée par l'admin : il fixe le ratio du recadrage et les largeurs des variantes (ADR 0002).
 * Le logo n'est pas recadré : ses variantes gardent le ratio du fichier (ADR 0006).
 */
enum ImageUsage: string
{
    case Article = 'article';
    case Product = 'product';
    case Logo = 'logo';

    /**
     * @return non-empty-list<positive-int> largeurs des variantes, de la plus petite à la plus grande
     */
    public function widths(): array
    {
        return match ($this) {
            self::Article => [480, 960],
            self::Product => [400, 640, 800],
            self::Logo => [200, 400],
        };
    }

    /**
     * @return array{positive-int, positive-int}|null ratio largeur, hauteur ; null : pas de recadrage
     */
    public function ratio(): ?array
    {
        return match ($this) {
            self::Article => [16, 9],
            self::Product => [1, 1],
            self::Logo => null,
        };
    }

    /**
     * @return positive-int
     */
    public function largestWidth(): int
    {
        return max($this->widths());
    }

    /**
     * @param array{int, int}|null $sourceSize largeur et hauteur du fichier, requises pour un usage sans recadrage
     *
     * @return positive-int
     */
    public function heightFor(int $width, ?array $sourceSize = null): int
    {
        [$ratioWidth, $ratioHeight] = $this->ratio() ?? $sourceSize ?? throw new \LogicException(\sprintf('L\'usage %s garde le ratio du fichier : ses dimensions sont requises.', $this->value));

        return max(1, intdiv($width * $ratioHeight, $ratioWidth));
    }
}
