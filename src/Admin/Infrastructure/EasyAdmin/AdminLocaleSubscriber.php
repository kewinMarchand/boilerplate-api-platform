<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\EasyAdmin;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Langue de l'interface de l'admin (SPEC-COMMUNE, section 29, ADR 0015), indépendante des langues du contenu : français
 * par défaut, anglais au choix. Le sélecteur d'EasyAdmin envoie ?_locale=… ; le choix est gardé en session. Priorité 20 :
 * après le routeur (32), qui pose le _locale par défaut de la route, avant LocaleListener (16), qui l'applique.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 20)]
final class AdminLocaleSubscriber
{
    public const string DEFAULT_LOCALE = 'fr';
    /** Code => nom de la langue, écrit dans cette langue. */
    public const array LOCALES = ['fr' => 'Français', 'en' => 'English'];
    private const string SESSION_KEY = '_admin_locale';

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || !str_starts_with($request->getPathInfo(), '/admin')) {
            return;
        }
        $session = $request->hasSession() ? $request->getSession() : null;
        $chosen = $request->query->get('_locale');
        if (\is_string($chosen) && isset(self::LOCALES[$chosen])) {
            $session?->set(self::SESSION_KEY, $chosen);
        } else {
            $chosen = $session?->get(self::SESSION_KEY);
        }
        $request->attributes->set('_locale', \is_string($chosen) && isset(self::LOCALES[$chosen]) ? $chosen : self::DEFAULT_LOCALE);
    }
}
