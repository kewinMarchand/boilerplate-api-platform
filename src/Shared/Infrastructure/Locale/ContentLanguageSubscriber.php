<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Locale;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Content-Language sur chaque réponse de l'API (documentation exclue), et Vary: Accept-Language, puisque la réponse en
 * dépend dès que le site a plusieurs langues.
 */
#[AsEventListener(event: KernelEvents::RESPONSE)]
final readonly class ContentLanguageSubscriber
{
    public function __construct(private ContentLocale $contentLocale)
    {
    }

    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || !ContentLocale::isApiRequest($event->getRequest())) {
            return;
        }
        $response = $event->getResponse();
        $response->headers->set('Content-Language', $this->contentLocale->current());
        $response->setVary('Accept-Language', false);
    }
}
