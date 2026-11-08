<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use ApiPlatform\Test\ApiTestCase;
use App\Media\Domain\ImageUsage;
use App\Tests\Support\TestImages;
use PHPUnit\Framework\Attributes\TestWith;

final class ArticleApiTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = false;

    /** Fichier audio de démonstration (fixtures/audio/article-demo.mp3), clé sha256("audio\n" + contenu) tronquée. */
    private const array DEMO_AUDIO = ['src' => 'http://localhost:8090/media/a57856e80eb0ae8c287540d0475c8fe5.mp3', 'type' => 'audio/mpeg', 'durationSeconds' => 4];

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
                'section' => ['slug' => 'actualites', 'name' => 'Actualités'],
                'format' => null,
                'audio' => null,
                'speaker' => '',
            ],
            [
                'id' => 2,
                'slug' => 'un-carrousel-accessible-sans-dependance',
                'title' => 'Un carrousel accessible sans dépendance',
                'excerpt' => 'Défilement natif, boutons explicites, pas de lecture automatique.',
                'publishedAt' => '2026-09-22',
                'image' => TestImages::demoView('blog-2', ImageUsage::Article, ''),
                'section' => ['slug' => 'conseils', 'name' => 'Conseils'],
                'format' => null,
                'audio' => self::DEMO_AUDIO,
                'speaker' => 'Présenté par Hina Teriierooiterai',
            ],
            [
                'id' => 1,
                'slug' => 'pourquoi-rendre-le-contenu-cote-serveur',
                'title' => 'Pourquoi rendre le contenu côté serveur',
                'excerpt' => 'Le contenu éditorial arrive dans le HTML initial : meilleur référencement et LCP.',
                'publishedAt' => '2026-09-15',
                'image' => TestImages::demoView('blog-1', ImageUsage::Article, ''),
                'section' => ['slug' => 'conseils', 'name' => 'Conseils'],
                'format' => ['slug' => 'guide', 'name' => 'Guide'],
                'audio' => null,
                'speaker' => '',
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
        self::assertSame(
            [['slug' => 'conseils', 'name' => 'Conseils'], null, self::DEMO_AUDIO, 'Présenté par Hina Teriierooiterai', '', ''],
            [$article['section'], $article['format'], $article['audio'], $article['speaker'], $article['metaTitle'], $article['metaDescription']],
        );
    }

    /**
     * @param list<int> $ids
     */
    #[TestWith(['section=conseils', [2, 1]])]
    #[TestWith(['section=actualites', [3]])]
    #[TestWith(['format=guide', [1]])]
    #[TestWith(['section=conseils&format=guide', [1]])]
    #[TestWith(['section=actualites&format=guide', []])]
    #[TestWith(['section=inconnue', []])]
    #[TestWith(['format=inconnu', []])]
    #[TestWith(['section=', [3, 2, 1]])]
    public function testFiltersBySectionAndFormat(string $query, array $ids): void
    {
        $response = static::createClient()->request('GET', '/api/articles?'.$query, ['headers' => ['Accept' => 'application/json']]);

        self::assertResponseIsSuccessful();
        self::assertSame($ids, array_column($response->toArray(), 'id'));
    }

    public function testListsSectionsAndFormatsByPosition(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/article-sections', ['headers' => ['Accept' => 'application/json']]);
        self::assertResponseIsSuccessful();
        self::assertJsonEquals([['slug' => 'conseils', 'name' => 'Conseils'], ['slug' => 'actualites', 'name' => 'Actualités']]);

        $client->request('GET', '/api/article-formats', ['headers' => ['Accept' => 'application/json']]);
        self::assertResponseIsSuccessful();
        self::assertJsonEquals([['slug' => 'guide', 'name' => 'Guide']]);
    }

    public function testTheDemoAudioIsServedUnderMedia(): void
    {
        self::assertFileExists(\dirname(__DIR__, 2).'/var/test-media/'.basename(self::DEMO_AUDIO['src']));
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
