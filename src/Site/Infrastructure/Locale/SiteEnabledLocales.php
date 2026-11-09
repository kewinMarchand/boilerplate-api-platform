<?php

declare(strict_types=1);

namespace App\Site\Infrastructure\Locale;

use App\Shared\Infrastructure\Locale\EnabledLocales;
use App\Site\Application\GetSiteIdentity;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Les langues enregistrées dans l'identité du site.
 */
#[AsAlias(EnabledLocales::class)]
final readonly class SiteEnabledLocales implements EnabledLocales
{
    public function __construct(private GetSiteIdentity $getSiteIdentity)
    {
    }

    public function enabled(): array
    {
        return ($this->getSiteIdentity)()->getLocales();
    }
}
