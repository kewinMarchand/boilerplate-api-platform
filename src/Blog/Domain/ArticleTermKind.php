<?php

declare(strict_types=1);

namespace App\Blog\Domain;

/**
 * Les deux listes qui classent les articles (ADR 0010) : la rubrique (sujet) et le format (forme du contenu).
 */
enum ArticleTermKind: string
{
    case Section = 'section';
    case Format = 'format';

    /**
     * Refus de suppression, avec l'accord du genre : « La rubrique X est utilisée… », « Le format X est utilisé… ».
     */
    public function usedBy(string $name, int $articles): string
    {
        return \sprintf(
            self::Section === $this ? 'La rubrique %s est utilisée par %d %s.' : 'Le format %s est utilisé par %d %s.',
            $name,
            $articles,
            1 === $articles ? 'article' : 'articles',
        );
    }

    public function slugTaken(string $slug): string
    {
        return \sprintf(self::Section === $this ? 'Le slug « %s » est déjà pris par une autre rubrique.' : 'Le slug « %s » est déjà pris par un autre format.', $slug);
    }

    public function missing(): string
    {
        return self::Section === $this ? 'Cette rubrique n\'existe plus.' : 'Ce format n\'existe plus.';
    }
}
