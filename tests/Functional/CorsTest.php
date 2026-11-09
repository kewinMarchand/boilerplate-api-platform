<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use ApiPlatform\Test\ApiTestCase;

final class CorsTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = false;

    public function testPreflightAllowsLocalFrontOriginAndScenarioHeader(): void
    {
        static::createClient()->request('OPTIONS', '/api/contact_messages', ['headers' => [
            'Origin' => 'http://localhost:3010',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'content-type, x-scenario',
        ]]);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('access-control-allow-origin', 'http://localhost:3010');
        self::assertResponseHeaderSame('access-control-allow-headers', 'content-type, accept, x-scenario');
    }

    public function testSimpleRequestCarriesAllowOrigin(): void
    {
        static::createClient()->request('GET', '/api/tasks', ['headers' => ['Origin' => 'http://localhost:5173']]);

        self::assertResponseHeaderSame('access-control-allow-origin', 'http://localhost:5173');
    }

    public function testTestBlockAndLoopbackOriginsAreAllowed(): void
    {
        foreach (['http://localhost:4002', 'http://127.0.0.1:4002', 'http://127.0.0.1:3010'] as $origin) {
            static::createClient()->request('GET', '/api/tasks', ['headers' => ['Origin' => $origin]]);

            self::assertResponseHeaderSame('access-control-allow-origin', $origin);
        }
    }

    public function testPortOutsideRegistryIsNotAllowed(): void
    {
        static::createClient()->request('GET', '/api/tasks', ['headers' => ['Origin' => 'http://localhost:3130']]);

        self::assertResponseNotHasHeader('access-control-allow-origin');
    }

    public function testUnknownOriginIsNotAllowed(): void
    {
        static::createClient()->request('OPTIONS', '/api/tasks', ['headers' => [
            'Origin' => 'https://exemple.fr',
            'Access-Control-Request-Method' => 'GET',
        ]]);

        self::assertResponseNotHasHeader('access-control-allow-origin');
    }
}
