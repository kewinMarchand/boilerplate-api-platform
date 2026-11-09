<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ApiPlatform\Scenario;

enum Scenario: string
{
    case Error = 'error';
    case Empty = 'empty';
    /** Le site active aussi l'anglais (BilingualScenarioLocales, ADR 0015). */
    case Bilingual = 'bilingual';
}
