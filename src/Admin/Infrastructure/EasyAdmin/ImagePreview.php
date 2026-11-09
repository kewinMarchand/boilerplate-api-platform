<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\EasyAdmin;

use App\Media\Domain\Image;
use App\Media\Domain\ImageUsage;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * Aperçu d'une image de l'admin (ADR 0002) : sa plus petite variante WebP (PNG pour une icône), servie par Caddy sous /media/ sur le même hôte
 * que l'admin, d'où une adresse relative.
 */
final class ImagePreview
{
    public static function url(?Image $image, ImageUsage $usage): ?string
    {
        return null === $image ? null : '/media/'.Image::fileName($image->key, self::previewWidth($usage), $usage->fallbackFormat());
    }

    /**
     * Aperçu d'un formulaire : l'image actuelle et son texte de remplacement, null sans image. La légende est traduite
     * au rendu, dans la langue de l'interface.
     *
     * @param string $label clé de traduction du domaine admin
     *
     * @return array{src: string, caption: TranslatableMessage}|null
     */
    public static function figure(?Image $image, ImageUsage $usage, string $label = 'image.current'): ?array
    {
        $url = self::url($image, $usage);
        if (null === $image || null === $url) {
            return null;
        }
        $label = new TranslatableMessage($label, [], AdminMessages::DOMAIN);
        $caption = match (true) {
            ImageUsage::Icon === $usage => $label,
            $image->isDecorative() => new TranslatableMessage('image.caption_decorative', ['%label%' => $label], AdminMessages::DOMAIN),
            default => new TranslatableMessage('image.caption_alt', ['%label%' => $label, '%alt%' => $image->alt], AdminMessages::DOMAIN),
        };

        return ['src' => $url, 'caption' => $caption];
    }

    /**
     * La plus petite variante, sauf pour l'icône, dont la plus petite (32 px) ne se voit pas.
     */
    private static function previewWidth(ImageUsage $usage): int
    {
        return ImageUsage::Icon === $usage ? 192 : $usage->widths()[0];
    }

    /**
     * Colonne « Image » des listes, vide pour une ligne sans image.
     *
     * @param callable(object): ?Image $image
     */
    public static function column(ImageUsage $usage, callable $image): TextField
    {
        return TextField::new('imagePreview', 'field.image')
            ->setVirtual(true)
            ->onlyOnIndex()
            ->formatValue(static fn (mixed $value, object $entity): ?string => self::url($image($entity), $usage))
            ->setTemplatePath('admin/field/image_preview.html.twig');
    }
}
