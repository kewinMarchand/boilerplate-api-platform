<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * Règle d'un module enfreinte. Le modèle de message, en français, sert aussi de clé de traduction : l'admin le traduit
 * dans la langue de son interface (domaine « violations », ADR 0015) avec ses paramètres ; le message de l'exception
 * reste le texte français, paramètres remplacés.
 */
abstract class RuleViolation extends \DomainException
{
    /**
     * @param string                    $template   modèle français, paramètres en %nom%
     * @param array<string, string|int> $parameters
     */
    public function __construct(
        public readonly string $template,
        public readonly array $parameters = [],
    ) {
        parent::__construct(strtr($template, array_map(strval(...), $parameters)));
    }
}
