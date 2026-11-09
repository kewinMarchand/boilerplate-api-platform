<?php

declare(strict_types=1);

namespace App\Content\Domain;

/**
 * Pages fixes des fronts dont l'admin règle le référencement (SPEC-COMMUNE, section 26, ADR 0011) ; la valeur est la
 * clé du contrat.
 */
enum MetaPage: string
{
    case Home = 'home';
    case Catalog = 'catalog';
    case Blog = 'blog';
    case Contact = 'contact';
    case Sitemap = 'sitemap';
    case LegalNotice = 'legal-notice';
    case Privacy = 'privacy';
    case Accessibility = 'accessibility';
}
