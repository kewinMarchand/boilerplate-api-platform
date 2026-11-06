<?php

declare(strict_types=1);

namespace App\Media\Domain;

/**
 * Une image envoyée enfreint une règle (type, poids, dimensions, texte de remplacement). Le message s'affiche tel quel
 * dans l'admin.
 */
final class MediaRuleViolation extends \DomainException
{
}
