<?php

declare(strict_types=1);

namespace App\Content\Domain;

/**
 * Une modification des contenus enfreint une de leurs règles. Le message s'affiche tel quel dans l'admin.
 */
final class ContentRuleViolation extends \DomainException
{
}
