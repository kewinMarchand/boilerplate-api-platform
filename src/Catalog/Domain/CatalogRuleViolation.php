<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

use App\Shared\Domain\RuleViolation;

/**
 * Une modification du catalogue enfreint une de ses règles. Le message s'affiche dans l'admin, traduit dans la langue de son interface.
 */
final class CatalogRuleViolation extends RuleViolation
{
}
