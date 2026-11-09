<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Kernel;
use App\Shared\Infrastructure\ApiPlatform\Scenario\ScenarioProvider;
use App\Shared\Infrastructure\Locale\BilingualScenarioLocales;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ScenarioRegistrationTest extends TestCase
{
    #[DataProvider('environments')]
    public function testScenariosAreRegisteredOutsideProductionOnly(string $environment, bool $registered): void
    {
        $kernel = new class($environment, false) extends Kernel {
            public function containerBuilder(): ContainerBuilder
            {
                $this->initializeBundles();

                return $this->buildContainer();
            }
        };

        $container = $kernel->containerBuilder();
        // X-Scenario: bilingual (ADR 0015) suit la même règle
        foreach ([ScenarioProvider::class, BilingualScenarioLocales::class] as $service) {
            $isRegistered = $container->hasDefinition($service) && !$container->getDefinition($service)->hasTag('container.excluded');
            self::assertSame($registered, $isRegistered, $service);
        }
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function environments(): iterable
    {
        yield 'dev' => ['dev', true];
        yield 'test' => ['test', true];
        yield 'prod' => ['prod', false];
    }
}
