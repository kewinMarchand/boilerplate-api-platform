<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\Catalog\Domain\CatalogRuleViolation;
use App\Catalog\Domain\Product;
use App\Media\Application\ChangeImage;
use App\Media\Domain\Image;
use App\Media\Domain\ImageUsage;
use App\Media\Domain\MediaRuleViolation;

/**
 * Galerie d'un produit après une saisie de l'admin (ADR 0009) : lignes retirées et lignes d'ajout vides écartées, nombre
 * vérifié avant tout encodage, puis tri par position.
 */
final class ProductGallery
{
    /**
     * @param list<GalleryImageData> $rows
     *
     * @return list<Image>
     *
     * @throws CatalogRuleViolation plus de 8 images
     * @throws MediaRuleViolation   image refusée
     */
    public static function change(ChangeImage $changeImage, array $rows, string $productName): array
    {
        $kept = array_values(array_filter($rows, static fn (GalleryImageData $row): bool => !$row->remove && (null !== $row->current || null !== $row->content)));
        Product::checkGallerySize(\count($kept));
        usort($kept, static fn (GalleryImageData $a, GalleryImageData $b): int => $a->position <=> $b->position);

        $images = [];
        foreach ($kept as $row) {
            $alt = '' === trim($row->alt) ? $productName : $row->alt;
            $image = $changeImage($row->current, $row->content, $alt, false, ImageUsage::Product);
            \assert(null !== $image);
            $images[] = $image;
        }

        return $images;
    }
}
