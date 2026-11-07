<?php

declare(strict_types=1);

namespace App\Media\Domain;

/**
 * Usage d'une image envoyée par l'admin : il fixe le ratio du recadrage et les largeurs des variantes (ADR 0002).
 */
enum ImageUsage: string
{
    case Article = 'article';
    case Product = 'product';

    /**
     * @return non-empty-list<positive-int> largeurs des variantes, de la plus petite à la plus grande
     */
    public function widths(): array
    {
        return match ($this) {
            self::Article => [480, 960],
            self::Product => [400, 640, 800],
        };
    }

    /**
     * @return array{positive-int, positive-int} ratio largeur, hauteur
     */
    public function ratio(): array
    {
        return match ($this) {
            self::Article => [16, 9],
            self::Product => [1, 1],
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
     * @return positive-int
     */
    public function heightFor(int $width): int
    {
        [$ratioWidth, $ratioHeight] = $this->ratio();

        return max(1, intdiv($width * $ratioHeight, $ratioWidth));
    }
}
