<?php

declare(strict_types=1);

namespace App\Content\Domain;

use App\Shared\Domain\RuleViolation;

/**
 * Une modification des contenus enfreint une de leurs règles. Le message s'affiche dans l'admin, traduit dans la langue de son interface.
 */
final class ContentRuleViolation extends RuleViolation
{
}
