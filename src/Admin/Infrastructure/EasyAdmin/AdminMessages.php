<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\EasyAdmin;

use App\Shared\Domain\RuleViolation;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Textes de l'admin dans la langue de son interface (ADR 0015) : libellés du domaine « admin », règles enfreintes du
 * domaine « violations », dont la clé est le modèle français du message.
 */
final readonly class AdminMessages
{
    public const string DOMAIN = 'admin';
    public const string VIOLATIONS = 'violations';

    public function __construct(private TranslatorInterface $translator)
    {
    }

    /**
     * @param array<string, string|int> $parameters
     */
    public function trans(string $key, array $parameters = []): string
    {
        return $this->translator->trans($key, $parameters, self::DOMAIN);
    }

    /**
     * Une règle enfreinte, ou un refus du domaine sans paramètres (prix négatif), traduit.
     */
    public function violation(\Exception $violation): string
    {
        return $violation instanceof RuleViolation
            ? $this->translator->trans($violation->template, $violation->parameters, self::VIOLATIONS)
            : $this->translator->trans($violation->getMessage(), [], self::VIOLATIONS);
    }
}
