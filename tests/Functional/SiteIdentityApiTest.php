<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use ApiPlatform\Test\ApiTestCase;

final class SiteIdentityApiTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = false;

    /**
     * Fixtures : valeurs du modèle, sans logo ni couleurs (le front garde les jetons de boilerplates-ui).
     */
    public const array DEFAULT = [
        'name' => 'Boilerplate',
        'tagline' => 'Modèle de site avec boilerplates-ui, outillé pour la QA et les tests end-to-end.',
        'logo' => null,
        'defaultColorScheme' => 'auto',
        'theme' => null,
    ];

    public function testReadsTheIdentityOfTheFixtures(): void
    {
        $response = static::createClient()->request('GET', '/api/site-identity', ['headers' => ['Accept' => 'application/json']]);

        self::assertResponseIsSuccessful();
        self::assertSame(self::DEFAULT, $response->toArray());
    }

    public function testIsReadOnly(): void
    {
        static::createClient()->request('PUT', '/api/site-identity', ['json' => ['name' => 'Autre nom']]);

        self::assertResponseStatusCodeSame(405);
    }
}
