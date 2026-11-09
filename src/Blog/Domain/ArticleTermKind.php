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
    public function usedBy(string $name, int $articles): BlogRuleViolation
    {
        $template = match (true) {
            self::Section === $this && 1 === $articles => 'La rubrique %name% est utilisée par 1 article.',
            self::Section === $this => 'La rubrique %name% est utilisée par %count% articles.',
            1 === $articles => 'Le format %name% est utilisé par 1 article.',
            default => 'Le format %name% est utilisé par %count% articles.',
        };

        return new BlogRuleViolation($template, ['%name%' => $name, '%count%' => $articles]);
    }

    public function slugTaken(string $slug): BlogRuleViolation
    {
        return new BlogRuleViolation(self::Section === $this ? 'Le slug « %slug% » est déjà pris par une autre rubrique.' : 'Le slug « %slug% » est déjà pris par un autre format.', ['%slug%' => $slug]);
    }

    public function missing(): BlogRuleViolation
    {
        return new BlogRuleViolation(self::Section === $this ? 'Cette rubrique n\'existe plus.' : 'Ce format n\'existe plus.');
    }
}
