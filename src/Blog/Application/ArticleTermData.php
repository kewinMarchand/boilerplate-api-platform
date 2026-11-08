<?php

declare(strict_types=1);

namespace App\Blog\Application;

use App\Blog\Domain\ArticleTerm;

/**
 * Saisie d'une rubrique ou d'un format, remplie par l'admin. La forme (slug, longueurs) est validée à la frontière.
 */
final class ArticleTermData
{
    public string $slug = '';
    public string $name = '';
    public int $position = 0;

    public static function fromTerm(ArticleTerm $term): self
    {
        $data = new self();
        $data->slug = $term->getSlug();
        $data->name = $term->getName();
        $data->position = $term->getPosition();

        return $data;
    }
}
