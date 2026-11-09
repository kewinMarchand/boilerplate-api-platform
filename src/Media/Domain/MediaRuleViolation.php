<?php

declare(strict_types=1);

namespace App\Media\Domain;

use App\Shared\Domain\RuleViolation;

/**
 * Une image ou un fichier audio envoyé enfreint une règle (type, poids, dimensions, texte de remplacement). Le message
 * s'affiche dans l'admin, traduit dans la langue de son interface.
 */
final class MediaRuleViolation extends RuleViolation
{
}
