<?php

declare(strict_types=1);

namespace App\Media\Domain;

/**
 * Image stockée, désignée par l'empreinte de son contenu. Un texte de remplacement vide la déclare décorative.
 * Pour un usage sans recadrage (logo), la hauteur de la plus grande variante dépend du fichier : l'image la porte.
 * Une source plus étroite que la plus grande variante de son usage la porte aussi, ramenée à sa largeur (ADR 0009) ;
 * null : la plus grande variante de l'usage.
 */
final readonly class Image
{
    private const string KEY_PATTERN = '/^[0-9a-f]{32}$/';

    public string $alt;

    public function __construct(
        public string $key,
        string $alt,
        public ?int $height = null,
        public ?int $width = null,
    ) {
        if (1 !== preg_match(self::KEY_PATTERN, $key)) {
            throw new MediaRuleViolation('Empreinte d\'image invalide : « %key% ».', ['%key%' => $key]);
        }
        $this->alt = trim($alt);
    }

    public function withAlt(string $alt): self
    {
        return new self($this->key, $alt, $this->height, $this->width);
    }

    public function isDecorative(): bool
    {
        return '' === $this->alt;
    }

    public static function fileName(string $key, int $width, ImageFormat $format): string
    {
        return \sprintf('%s-%d.%s', $key, $width, $format->value);
    }
}
