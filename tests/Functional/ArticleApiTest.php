<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use ApiPlatform\Test\ApiTestCase;
use App\Media\Domain\ImageUsage;
use App\Tests\Support\TestImages;

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
                'image' => TestImages::demoView('blog-3', ImageUsage::Article, ''),
            ],
            [
                'id' => 2,
                'slug' => 'un-carrousel-accessible-sans-dependance',
                'title' => 'Un carrousel accessible sans dépendance',
                'excerpt' => 'Défilement natif, boutons explicites, pas de lecture automatique.',
                'publishedAt' => '2026-09-22',
                'image' => TestImages::demoView('blog-2', ImageUsage::Article, ''),
            ],
            [
                'id' => 1,
                'slug' => 'pourquoi-rendre-le-contenu-cote-serveur',
                'title' => 'Pourquoi rendre le contenu côté serveur',
                'excerpt' => 'Le contenu éditorial arrive dans le HTML initial : meilleur référencement et LCP.',
                'publishedAt' => '2026-09-15',
                'image' => TestImages::demoView('blog-1', ImageUsage::Article, ''),
            ],
        ]);
    }

    public function testReadsAnArticleBySlugWithItsTextInHtml(): void
    {
        $response = static::createClient()->request('GET', '/api/articles/un-carrousel-accessible-sans-dependance', ['headers' => ['Accept' => 'application/json']]);

        self::assertResponseIsSuccessful();
        $article = $response->toArray();
        self::assertSame(
            [2, 'un-carrousel-accessible-sans-dependance', 'Un carrousel accessible sans dépendance', '2026-09-22'],
            [$article['id'], $article['slug'], $article['title'], $article['publishedAt']],
        );
        self::assertSame(TestImages::demoView('blog-2', ImageUsage::Article, ''), $article['image']);
        self::assertIsString($article['html']);
        self::assertStringStartsWith('<p>Un carrousel est souvent', $article['html']);
        self::assertStringContainsString('<h2>Partir du défilement natif</h2>', $article['html']);
        self::assertStringContainsString('<code>overflow-x: auto</code>', $article['html']);
        self::assertStringContainsString('<li>', $article['html']);
    }

    public function testAnswers404ForAnUnknownSlug(): void
    {
        static::createClient()->request('GET', '/api/articles/inconnu', ['headers' => ['Accept' => 'application/json']]);

        self::assertResponseStatusCodeSame(404);
    }

    public function testIsReadOnly(): void
    {
        static::createClient()->request('POST', '/api/articles', ['json' => ['title' => 'Nouvel article']]);

        self::assertResponseStatusCodeSame(405);
    }
}
