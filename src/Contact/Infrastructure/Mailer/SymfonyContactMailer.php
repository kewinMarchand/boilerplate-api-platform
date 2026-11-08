<?php

declare(strict_types=1);

namespace App\Contact\Infrastructure\Mailer;

use App\Contact\Domain\ContactMail;
use App\Contact\Domain\ContactMailer;
use App\Site\Application\GetSiteIdentity;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Exception\RfcComplianceException;

/**
 * Envoi par SMTP (MAILER_DSN) à l'adresse qui reçoit les messages, saisie dans l'identité du site (ADR 0014).
 */
final readonly class SymfonyContactMailer implements ContactMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private GetSiteIdentity $getSiteIdentity,
        private LoggerInterface $logger,
        #[Autowire(env: 'MAILER_FROM')]
        private string $from,
    ) {
    }

    public function send(ContactMail $mail): void
    {
        try {
            $this->mailer->send((new Email())
                ->from($this->from)
                ->to(($this->getSiteIdentity)()->getContactRecipient())
                ->replyTo($mail->replyTo)
                ->subject($mail->subject)
                ->text($mail->body));
        } catch (TransportExceptionInterface|RfcComplianceException $exception) {
            // RfcComplianceException : le format HTML5 validé à la frontière accepte quelques adresses que la RFC refuse
            $this->logger->error('Message de contact enregistré mais non envoyé par mail : {error}', ['error' => $exception->getMessage(), 'exception' => $exception]);
        }
    }
}
