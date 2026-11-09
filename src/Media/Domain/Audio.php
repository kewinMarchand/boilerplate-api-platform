<?php

declare(strict_types=1);

namespace App\Media\Domain;

/**
 * Fichier audio stocké tel qu'envoyé (ADR 0010), désigné par l'empreinte de son contenu, avec sa durée lue à l'envoi.
 */
final readonly class Audio
{
    private const string KEY_PATTERN = '/^[0-9a-f]{32}$/';

    public function __construct(
        public string $key,
        public AudioFormat $format,
        public int $durationSeconds,
    ) {
        if (1 !== preg_match(self::KEY_PATTERN, $key)) {
            throw new MediaRuleViolation('Empreinte de fichier audio invalide : « %key% ».', ['%key%' => $key]);
        }
        if ($durationSeconds < 0) {
            throw new MediaRuleViolation('La durée d\'un fichier audio ne peut pas être négative.');
        }
    }

    public function fileName(): string
    {
        return $this->key.'.'.$this->format->value;
    }
}
