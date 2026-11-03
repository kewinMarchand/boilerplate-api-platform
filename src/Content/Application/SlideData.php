<?php

declare(strict_types=1);

namespace App\Content\Application;

use App\Content\Domain\Slide;

/**
 * Saisie d'une diapositive, remplie par l'admin. La forme (slug, longueurs) est validée à la frontière.
 */
final class SlideData
{
    public string $slug = '';
    public string $title = '';
    public string $text = '';
    public int $image = 1;
    public int $position = 1;

    public static function fromSlide(Slide $slide): self
    {
        $data = new self();
        $data->slug = $slide->getSlug();
        $data->title = $slide->getTitle();
        $data->text = $slide->getText();
        $data->image = $slide->getImage();
        $data->position = $slide->getPosition();

        return $data;
    }
}
