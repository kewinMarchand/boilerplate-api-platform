<?php

declare(strict_types=1);

namespace App\Blog\Domain;

/**
 * Une modification du blog enfreint une de ses règles. Le message s'affiche tel quel dans l'admin.
 */
final class BlogRuleViolation extends \DomainException
{
}
