<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Locale;

use App\Shared\Infrastructure\ApiPlatform\Scenario\Scenario;
use App\Shared\Infrastructure\ApiPlatform\Scenario\ScenarioProvider;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * X-Scenario: bilingual (dev et test seulement) : le site active aussi l'anglais pour cette requête, ce qui réveille les
 * traductions anglaises des fixtures. C'est le cas bilingue des tests et de make contract (ADR 0015).
 */
#[When(env: 'dev')]
#[When(env: 'test')]
#[AsDecorator(decorates: EnabledLocales::class)]
final readonly class BilingualScenarioLocales implements EnabledLocales
{
    public const string SECOND_LOCALE = 'en';

    public function __construct(
        #[AutowireDecorated]
        private EnabledLocales $inner,
        private RequestStack $requests,
    ) {
    }

    public function enabled(): array
    {
        $locales = $this->inner->enabled();
        $header = $this->requests->getMainRequest()?->headers->get(ScenarioProvider::HEADER);
        if (Scenario::Bilingual !== Scenario::tryFrom((string) $header) || \in_array(self::SECOND_LOCALE, $locales, true)) {
            return $locales;
        }

        return [...$locales, self::SECOND_LOCALE];
    }
}
