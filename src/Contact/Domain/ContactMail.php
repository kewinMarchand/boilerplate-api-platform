<?php

declare(strict_types=1);

namespace App\Contact\Domain;

/**
 * Mail qui transmet un message de contact (SPEC-COMMUNE, section 27). Texte brut seulement : rien de saisi n'est interprété.
 */
final readonly class ContactMail
{
    private function __construct(
        public string $subject,
        public string $body,
        public string $replyTo,
    ) {
    }

    public static function for(ContactMessage $message, ?InquiredProduct $product): self
    {
        $subject = null === $product
            ? \sprintf('Message de contact : %s', $message->getName())
            : \sprintf('Demande de renseignement : %s', $product->name);

        $lines = [
            \sprintf('Nom : %s', $message->getName()),
            \sprintf('Adresse : %s', $message->getEmail()),
        ];
        if (null !== $product) {
            $lines[] = \sprintf('Produit : %s (%s)', $product->name, $product->slug);
        }
        $lines[] = '';
        $lines[] = 'Message :';
        $lines[] = $message->getMessage();

        return new self($subject, implode("\n", $lines)."\n", $message->getEmail());
    }
}
