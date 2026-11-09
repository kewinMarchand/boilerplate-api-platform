<?php

declare(strict_types=1);

namespace App\Site\Domain;

use App\Shared\Domain\RuleViolation;

/**
 * Une modification de l'identité du site enfreint une de ses règles. Le message s'affiche dans l'admin, traduit dans la langue de son interface.
 */
final class SiteRuleViolation extends RuleViolation
{
}
