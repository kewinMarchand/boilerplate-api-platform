<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use ApiPlatform\Test\ApiTestCase;

final class TaskApiTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = false;

    public function testListsTasksAsPlainJson(): void
    {
        static::createClient()->request('GET', '/api/tasks', ['headers' => ['Accept' => 'application/json']]);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertJsonEquals([
            ['id' => 1, 'title' => 'Brancher la vraie API', 'done' => false],
            ['id' => 2, 'title' => 'Générer les types OpenAPI', 'done' => false],
            ['id' => 3, 'title' => 'Lancer make qa', 'done' => true],
        ]);
    }

    public function testListsTasksAsJsonLdWithoutPagination(): void
    {
        $response = static::createClient()->request('GET', '/api/tasks', ['headers' => ['Accept' => 'application/ld+json']]);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/ld+json');
        self::assertJsonContains(['@type' => 'Collection', 'totalItems' => 3]);
        self::assertArrayNotHasKey('view', $response->toArray());
    }

    public function testIsReadOnly(): void
    {
        static::createClient()->request('POST', '/api/tasks', ['json' => ['title' => 'Nouvelle tâche']]);

        self::assertResponseStatusCodeSame(405);
    }
}
