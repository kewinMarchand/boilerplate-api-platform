<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

/**
 * Une modification du catalogue enfreint une de ses règles. Le message s'affiche tel quel dans l'admin.
 */
final class CatalogRuleViolation extends \DomainException
{
}
