<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\Catalog\Domain\Product;
use App\Media\Domain\Image;

/**
 * Une ligne de la galerie d'un produit : une image enregistrée (current) ou une ligne d'ajout, vide tant qu'aucun
 * fichier n'y est envoyé.
 */
final class GalleryImageData
{
    public ?Image $current = null;
    /** Contenu du fichier envoyé : remplace l'image de la ligne, ou l'ajoute. */
    public ?string $content = null;
    /** Vide : le nom du produit. */
    public string $alt = '';
    /** @var array<string, string|null> texte de remplacement dans chaque langue traduite (ADR 0015) ; vide : celui de la langue par défaut */
    public array $alts = [];
    /** Ordre d'affichage, croissant ; à égalité, l'ordre des lignes. */
    public int $position = 0;
    public bool $remove = false;

    /**
     * @param array<string, array<string, string>> $translations traductions du produit
     */
    public static function fromImage(Image $image, int $position, array $translations = []): self
    {
        $data = new self();
        $data->current = $image;
        $data->alt = $image->alt;
        $data->position = $position;
        foreach ($translations as $locale => $fields) {
            $data->alts[$locale] = $fields[Product::GALLERY_ALT.$image->key] ?? '';
        }

        return $data;
    }
}
