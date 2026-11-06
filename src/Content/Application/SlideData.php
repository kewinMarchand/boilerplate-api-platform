<?php

declare(strict_types=1);

namespace App\Content\Application;

use App\Content\Domain\Slide;
use App\Media\Domain\Image;

/**
 * Saisie d'une diapositive, remplie par l'admin. La forme (slug, longueurs, fichier) est validée à la frontière.
 */
final class SlideData
{
    public string $slug = '';
    public string $title = '';
    public string $text = '';
    public int $position = 1;
    /** Contenu du fichier envoyé, null pour garder l'image actuelle. */
    public ?string $imageContent = null;
    public string $imageAlt = '';
    public bool $imageDecorative = false;
    public ?Image $currentImage = null;

    public static function fromSlide(Slide $slide): self
    {
        $data = new self();
        $data->slug = $slide->getSlug();
        $data->title = $slide->getTitle();
        $data->text = $slide->getText();
        $data->position = $slide->getPosition();
        $data->currentImage = $slide->getImage();
        $data->imageAlt = $data->currentImage->alt ?? '';
        $data->imageDecorative = $data->currentImage?->isDecorative() ?? false;

        return $data;
    }
}
