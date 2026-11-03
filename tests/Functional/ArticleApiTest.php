<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use ApiPlatform\Test\ApiTestCase;

final class ArticleApiTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = false;

    public function testListsArticlesLatestFirst(): void
    {
        static::createClient()->request('GET', '/api/articles', ['headers' => ['Accept' => 'application/json']]);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertJsonEquals([
            [
                'id' => 3,
                'slug' => 'le-mode-accessibilite-renforcee',
                'title' => 'Le mode accessibilité renforcée',
                'excerpt' => 'Texte agrandi, espacements WCAG 1.4.12 et contraste AAA, sans logique dupliquée.',
                'publishedAt' => '2026-10-01',
            ],
            [
                'id' => 2,
                'slug' => 'un-carrousel-accessible-sans-dependance',
                'title' => 'Un carrousel accessible sans dépendance',
                'excerpt' => 'Défilement natif, boutons explicites, pas de lecture automatique.',
                'publishedAt' => '2026-09-22',
            ],
            [
                'id' => 1,
                'slug' => 'pourquoi-rendre-le-contenu-cote-serveur',
                'title' => 'Pourquoi rendre le contenu côté serveur',
                'excerpt' => 'Le contenu éditorial arrive dans le HTML initial : meilleur référencement et LCP.',
                'publishedAt' => '2026-09-15',
            ],
        ]);
    }

    public function testIsReadOnly(): void
    {
        static::createClient()->request('POST', '/api/articles', ['json' => ['title' => 'Nouvel article']]);

        self::assertResponseStatusCodeSame(405);
    }
}
