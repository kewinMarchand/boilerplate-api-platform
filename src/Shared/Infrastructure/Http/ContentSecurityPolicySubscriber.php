<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * CSP d'une API JSON : rien à charger, rien à afficher en iframe. Même en-tête que boilerplate-hono.
 * Les réponses HTML (documentation Swagger UI) en sont exclues : elles chargent leurs scripts et leurs styles.
 */
final class ContentSecurityPolicySubscriber implements EventSubscriberInterface
{
    public const string POLICY = "default-src 'none'; frame-ancestors 'none'";

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onResponse'];
    }

    public function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        // Le Content-Type d'une page Twig n'est posé qu'à l'envoi : le format de la requête le donne avant
        $type = $response->headers->get('Content-Type') ?? $request->getMimeType((string) $request->getRequestFormat());
        if (!$event->isMainRequest() || str_starts_with((string) $type, 'text/html')) {
            return;
        }
        $response->headers->set('Content-Security-Policy', self::POLICY);
    }
}
