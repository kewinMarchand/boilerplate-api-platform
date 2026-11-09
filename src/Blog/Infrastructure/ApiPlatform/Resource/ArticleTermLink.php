<?php

declare(strict_types=1);

namespace App\Blog\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use App\Blog\Domain\ArticleTerm;

/**
 * Rubrique ou format d'un article, dans le contrat de l'article (ADR 0010).
 */
final readonly class ArticleTermLink
{
    public function __construct(
        #[ApiProperty(required: true)]
        public string $slug,
        #[ApiProperty(required: true)]
        public string $name,
    ) {
    }

    /**
     * @return ($term is null ? null : self)
     */
    public static function fromModel(?ArticleTerm $term, string $locale): ?self
    {
        return null === $term ? null : new self($term->getSlug($locale), $term->getName($locale));
    }
}
