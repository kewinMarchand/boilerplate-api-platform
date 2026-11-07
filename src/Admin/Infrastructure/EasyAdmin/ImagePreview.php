<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\EasyAdmin;

use App\Media\Domain\Image;
use App\Media\Domain\ImageFormat;
use App\Media\Domain\ImageUsage;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Aperçu d'une image de l'admin (ADR 0002) : sa plus petite variante WebP, servie par Caddy sous /media/ sur le même hôte
 * que l'admin, d'où une adresse relative.
 */
final class ImagePreview
{
    public static function url(?Image $image, ImageUsage $usage): ?string
    {
        return null === $image ? null : '/media/'.Image::fileName($image->key, $usage->widths()[0], ImageFormat::Webp);
    }

    /**
     * Colonne « Image » des listes, vide pour une ligne sans image.
     *
     * @param callable(object): ?Image $image
     */
    public static function column(ImageUsage $usage, callable $image): TextField
    {
        return TextField::new('imagePreview', 'Image')
            ->setVirtual(true)
            ->onlyOnIndex()
            ->formatValue(static fn (mixed $value, object $entity): ?string => self::url($image($entity), $usage))
            ->setTemplatePath('admin/field/image_preview.html.twig');
    }
}
