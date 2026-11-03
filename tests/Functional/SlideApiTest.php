<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use ApiPlatform\Test\ApiTestCase;

final class SlideApiTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = false;

    public function testListsSlidesInCarouselOrder(): void
    {
        $response = static::createClient()->request('GET', '/api/slides', ['headers' => ['Accept' => 'application/json']]);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertSame(['heliconia', 'anthurium', 'calliandra', 'strelitzia', 'nenuphar'], array_column($response->toArray(), 'slug'));
        self::assertJsonContains([[
            'id' => 1,
            'slug' => 'heliconia',
            'title' => 'Heliconia',
            'text' => 'Des bractées rouges et jaunes en pinces de homard, qui tiennent des semaines au jardin.',
            'image' => 1,
        ]]);
    }

    public function testIsReadOnly(): void
    {
        static::createClient()->request('POST', '/api/slides', ['json' => ['title' => 'Nouvelle diapositive']]);

        self::assertResponseStatusCodeSame(405);
    }
}
