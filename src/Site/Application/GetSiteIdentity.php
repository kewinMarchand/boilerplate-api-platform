<?php

declare(strict_types=1);

namespace App\Site\Application;

use App\Site\Domain\SiteIdentity;
use App\Site\Domain\SiteIdentityRepository;

/**
 * L'identité enregistrée, ou les valeurs du modèle sur une base où l'admin ne l'a jamais modifiée.
 */
final readonly class GetSiteIdentity
{
    public function __construct(private SiteIdentityRepository $identities)
    {
    }

    public function __invoke(): SiteIdentity
    {
        return $this->identities->find() ?? SiteIdentity::byDefault();
    }
}
