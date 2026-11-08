<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;

/**
 * Décore le mailer de test : tant que $failing est vrai, l'envoi échoue comme un serveur SMTP injoignable.
 * Interrupteur statique, car chaque requête de test démarre son propre conteneur. Les tests le remettent à faux.
 */
final class FailingMailer implements MailerInterface
{
    public static bool $failing = false;

    public function __construct(private readonly MailerInterface $inner)
    {
    }

    public function send(RawMessage $message, ?Envelope $envelope = null): void
    {
        if (self::$failing) {
            throw new TransportException('Connexion au serveur SMTP impossible.');
        }
        $this->inner->send($message, $envelope);
    }
}
