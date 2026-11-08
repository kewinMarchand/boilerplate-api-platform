<?php

declare(strict_types=1);

namespace App\Site\Domain;

/**
 * Une modification de l'identité du site enfreint une de ses règles. Le message s'affiche tel quel dans l'admin.
 */
final class SiteRuleViolation extends \DomainException
{
}
