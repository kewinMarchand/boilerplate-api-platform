<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use ApiPlatform\Test\ApiTestCase;

final class CategoryApiTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = false;

    public function testReturnsTheFullTree(): void
    {
        static::createClient()->request('GET', '/api/categories', ['headers' => ['Accept' => 'application/json']]);

        self::assertResponseIsSuccessful();
        self::assertJsonEquals([
            self::node('plantes-interieur', 'Plantes d’intérieur', null, [
                self::node('feuillages', 'Feuillages', 'plantes-interieur', [
                    self::node('monstera', 'Monstera', 'feuillages'),
                    self::node('fougeres', 'Fougères', 'feuillages'),
                ]),
                self::node('plantes-a-fleurs', 'Plantes à fleurs', 'plantes-interieur', [
                    self::node('anthurium', 'Anthurium', 'plantes-a-fleurs'),
                    self::node('strelitzia', 'Strelitzia', 'plantes-a-fleurs'),
                ]),
            ]),
            self::node('plantes-exterieur', 'Plantes d’extérieur', null, [
                self::node('palmiers', 'Palmiers', 'plantes-exterieur'),
                self::node('arbustes-a-fleurs', 'Arbustes à fleurs', 'plantes-exterieur', [
                    self::node('heliconia', 'Heliconia', 'arbustes-a-fleurs'),
                    self::node('calliandra', 'Calliandra', 'arbustes-a-fleurs'),
                ]),
            ]),
            self::node('plantes-aquatiques', 'Plantes aquatiques', null, [
                self::node('nenuphars', 'Nénuphars', 'plantes-aquatiques'),
            ]),
        ]);
    }

    public function testIsReadOnly(): void
    {
        static::createClient()->request('POST', '/api/categories', ['json' => ['name' => 'Cactus']]);

        self::assertResponseStatusCodeSame(405);
    }

    /**
     * @param list<array<string, mixed>> $children
     *
     * @return array<string, mixed>
     */
    private static function node(string $slug, string $name, ?string $parent, array $children = []): array
    {
        return ['slug' => $slug, 'name' => $name, 'parent' => $parent, 'children' => $children, 'alternates' => []];
    }
}
