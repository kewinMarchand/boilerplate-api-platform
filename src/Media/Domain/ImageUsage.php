<?php

declare(strict_types=1);

namespace App\Media\Domain;

/**
 * Usage d'une image envoyée par l'admin : il fixe le ratio du recadrage, les largeurs et les formats des variantes
 * (ADR 0002). Le logo n'est pas recadré : ses variantes gardent le ratio du fichier (ADR 0006). Une source plus étroite
 * que la plus grande variante n'est jamais agrandie : ses variantes s'arrêtent à sa largeur (ADR 0009).
 */
enum ImageUsage: string
{
    case Article = 'article';
    case Product = 'product';
    case Logo = 'logo';
    case Share = 'share';
    case Icon = 'icon';

    /**
     * @return non-empty-list<positive-int> largeurs des variantes, de la plus petite à la plus grande
     */
    public function widths(): array
    {
        return match ($this) {
            self::Article => [480, 960],
            self::Product => [400, 640, 800],
            self::Logo => [200, 400],
            self::Share => [1200],
            self::Icon => [32, 180, 192, 512],
        };
    }

    /**
     * @return array{positive-int, positive-int}|null ratio largeur, hauteur ; null : pas de recadrage
     */
    public function ratio(): ?array
    {
        return match ($this) {
            self::Article => [16, 9],
            self::Product, self::Icon => [1, 1],
            self::Logo => null,
            self::Share => [40, 21],
        };
    }

    /**
     * @return non-empty-list<ImageFormat> formats des variantes, dans l'ordre des sources du <picture>
     */
    public function formats(): array
    {
        return self::Icon === $this ? [ImageFormat::Png] : [ImageFormat::Avif, ImageFormat::Webp];
    }

    /**
     * Format lu par tous les navigateurs : celui de l'attribut src et des aperçus de l'admin.
     */
    public function fallbackFormat(): ImageFormat
    {
        return self::Icon === $this ? ImageFormat::Png : ImageFormat::Webp;
    }

    /**
     * @return positive-int
     */
    public function largestWidth(): int
    {
        return max($this->widths());
    }

    /**
     * Largeur minimale de la source, une fois recadrée : la plus grande variante, sauf pour les produits (ADR 0009).
     *
     * @return positive-int
     */
    public function minimumWidth(): int
    {
        return self::Product === $this ? $this->widths()[0] : $this->largestWidth();
    }

    /**
     * Largeurs générées pour une source de cette largeur (recadrée) : celles de l'usage plus étroites que la source,
     * puis la plus grande, ramenée à la largeur de la source si elle la dépasse.
     *
     * @param positive-int $sourceWidth au moins minimumWidth()
     *
     * @return non-empty-list<positive-int>
     */
    public function widthsUpTo(int $sourceWidth): array
    {
        $largest = min($sourceWidth, $this->largestWidth());
        $widths = array_values(array_filter($this->widths(), static fn (int $width): bool => $width < $largest));
        $widths[] = $largest;

        return $widths;
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
