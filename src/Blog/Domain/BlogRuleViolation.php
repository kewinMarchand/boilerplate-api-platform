<?php

declare(strict_types=1);

namespace App\Blog\Domain;

use App\Shared\Domain\RuleViolation;

/**
 * Une modification du blog enfreint une de ses règles. Le message s'affiche dans l'admin, traduit dans la langue de son interface.
 */
final class BlogRuleViolation extends RuleViolation
{
}
