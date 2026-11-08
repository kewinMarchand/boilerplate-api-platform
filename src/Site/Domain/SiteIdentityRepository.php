<?php

declare(strict_types=1);

namespace App\Site\Domain;

interface SiteIdentityRepository
{
    /**
     * L'identité enregistrée, ou null tant que l'admin ne l'a jamais modifiée.
     */
    public function find(): ?SiteIdentity;

    public function save(SiteIdentity $identity): void;
}
