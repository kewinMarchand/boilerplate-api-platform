<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use ApiPlatform\Test\ApiTestCase;
use App\Content\Infrastructure\Doctrine\Fixtures\PageMetaFixtures;
use App\Site\Domain\SiteIdentity;
use App\Site\Infrastructure\Doctrine\Fixtures\SiteIdentityFixtures;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Contenu multilingue (SPEC-COMMUNE, section 29, ADR 0015). Les fixtures n'activent que le français ; leurs traductions
 * anglaises ne servent qu'avec X-Scenario: bilingual, qui active aussi l'anglais.
 */
final class MultilingualApiTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = false;

    private const array BILINGUAL = ['X-Scenario' => 'bilingual'];

    public function testAMonolingualSiteIgnoresTheRequestedLanguage(): void
    {
        $response = $this->get('/api/products/monstera-deliciosa?locale=en', ['Accept-Language' => 'en']);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Language', 'fr');
        self::assertSame('Monstera deliciosa', $response->toArray()['name']);
        self::assertSame([], $response->toArray()['alternates']);
        self::assertSame(['fr'], $this->get('/api/site-identity')->toArray()['locales']);
        $this->get('/api/products/swiss-cheese-plant?locale=en');
        self::assertResponseStatusCodeSame(404);
    }

    public function testEveryApiResponseCarriesContentLanguageAndVariesOnAcceptLanguage(): void
    {
        $response = $this->get('/api/tasks');

        self::assertResponseHeaderSame('Content-Language', 'fr');
        self::assertContains('Accept-Language', $response->getHeaders()['vary'] ?? []);
        $this->get('/api/products/inconnu', ['Accept-Language' => 'en', ...self::BILINGUAL]);
        self::assertResponseStatusCodeSame(404);
        self::assertResponseHeaderSame('Content-Language', 'en');
    }

    /**
     * @param array<string, string> $headers
     */
    #[DataProvider('requestedLanguages')]
    public function testChoosesTheLanguageFromTheParameterThenAcceptLanguage(string $query, array $headers, string $expected): void
    {
        $this->get('/api/site-identity'.$query, [...$headers, ...self::BILINGUAL]);

        self::assertResponseHeaderSame('Content-Language', $expected);
    }

    /**
     * @return iterable<string, array{string, array<string, string>, string}>
     */
    public static function requestedLanguages(): iterable
    {
        // Request::create() du client de test envoie « en-us,en;q=0.5 » par défaut
        yield 'sans préférence : langue par défaut' => ['', ['Accept-Language' => ''], 'fr'];
        yield 'Accept-Language' => ['', ['Accept-Language' => 'en'], 'en'];
        yield 'région ignorée' => ['', ['Accept-Language' => 'en-GB'], 'en'];
        yield 'première langue activée, par poids' => ['', ['Accept-Language' => 'de, en;q=0.8, fr;q=0.5'], 'en'];
        yield 'poids plus fort écrit après' => ['', ['Accept-Language' => 'fr;q=0.4, en'], 'en'];
        yield 'poids nul exclu' => ['', ['Accept-Language' => 'en;q=0'], 'fr'];
        yield 'langue non activée' => ['', ['Accept-Language' => 'de'], 'fr'];
        yield 'le paramètre l\'emporte' => ['?locale=fr', ['Accept-Language' => 'en'], 'fr'];
        yield 'paramètre seul' => ['?locale=en', ['Accept-Language' => ''], 'en'];
        yield 'paramètre sans casse ni région' => ['?locale=EN-us', [], 'en'];
        yield 'paramètre non activé ignoré' => ['?locale=de', ['Accept-Language' => 'en'], 'en'];
    }

    public function testServesTheTranslationAndFallsBackFieldByField(): void
    {
        $product = $this->get('/api/products/swiss-cheese-plant?locale=en', self::BILINGUAL)->toArray();

        self::assertSame('Swiss cheese plant', $product['name']);
        self::assertSame('Swiss cheese plant (Monstera deliciosa)', $product['metaTitle']);
        // Non traduits : la valeur française
        self::assertStringStartsWith('Grandes feuilles vert profond', self::text($product, 'description'));
        self::assertSame('Monstera deliciosa', self::at($product, 'image', 'alt'));
        self::assertSame('monstera', $product['categorySlug']);
        self::assertSame([
            ['slug' => 'indoor-plants', 'name' => 'Indoor plants'],
            ['slug' => 'foliage-plants', 'name' => 'Foliage plants'],
            ['slug' => 'monstera', 'name' => 'Monstera'],
        ], $product['categoryPath']);
        self::assertSame([
            ['code' => 'feuillage', 'label' => 'Foliage', 'type' => 'choice', 'value' => 'persistant', 'valueLabel' => 'Evergreen', 'unit' => null],
            ['code' => 'hauteur-adulte', 'label' => 'Mature height', 'type' => 'number', 'value' => '300', 'valueLabel' => '300', 'unit' => 'cm'],
        ], $product['attributes']);
        self::assertSame([['locale' => 'fr', 'slug' => 'monstera-deliciosa'], ['locale' => 'en', 'slug' => 'swiss-cheese-plant']], $product['alternates']);
    }

    public function testASlugBelongsToItsLanguage(): void
    {
        $this->get('/api/products/monstera-deliciosa?locale=en', self::BILINGUAL);
        self::assertResponseStatusCodeSame(404);
        $this->get('/api/products/swiss-cheese-plant?locale=fr', self::BILINGUAL);
        self::assertResponseStatusCodeSame(404);

        // Sans slug traduit, celui de la langue par défaut sert dans les deux langues
        $untranslated = $this->get('/api/products/monstera-adansonii?locale=en', self::BILINGUAL)->toArray();
        self::assertSame([['locale' => 'fr', 'slug' => 'monstera-adansonii'], ['locale' => 'en', 'slug' => 'monstera-adansonii']], $untranslated['alternates']);
    }

    public function testFiltersTheCatalogByTheSlugOfTheLanguage(): void
    {
        $products = $this->get('/api/products?locale=en&category=foliage-plants&itemsPerPage=48', self::BILINGUAL)->toArray();
        self::assertCount(6, $products);
        self::assertContains('swiss-cheese-plant', array_column($products, 'slug'));
        self::assertContains('Swiss cheese plant', array_column($products, 'name'));

        $this->get('/api/products?locale=en&category=feuillages', self::BILINGUAL);
        self::assertResponseStatusCodeSame(404);

        $facets = $this->get('/api/product-facets?locale=en&category=indoor-plants', self::BILINGUAL)->toArray();
        self::assertSame('foliage-plants', self::at($facets, 'category', 0, 'slug'));
        self::assertSame('Foliage plants', self::at($facets, 'category', 0, 'name'));
        self::assertSame('Foliage', self::at($facets, 'attributes', 0, 'label'));
        self::assertSame('Evergreen', self::at($facets, 'attributes', 0, 'values', 0, 'label'));
        self::assertSame('Deciduous', self::at($facets, 'attributes', 0, 'values', 1, 'label'));
    }

    public function testSortsByTheNameOfTheLanguage(): void
    {
        $names = array_column($this->get('/api/products?locale=en&order[name]=desc&itemsPerPage=48', self::BILINGUAL)->toArray(), 'name');

        self::assertSame('Swiss cheese plant', $names[0]);
    }

    public function testTranslatesTheCategoryTree(): void
    {
        $tree = $this->get('/api/categories?locale=en', self::BILINGUAL)->toArray();

        self::assertSame('indoor-plants', self::at($tree, 0, 'slug'));
        self::assertSame('Indoor plants', self::at($tree, 0, 'name'));
        self::assertSame([['locale' => 'fr', 'slug' => 'plantes-interieur'], ['locale' => 'en', 'slug' => 'indoor-plants']], self::at($tree, 0, 'alternates'));
        self::assertSame('indoor-plants', self::at($tree, 0, 'children', 0, 'parent'));
    }

    public function testTranslatesArticlesAndTheirSections(): void
    {
        $article = $this->get('/api/articles/enhanced-accessibility-mode?locale=en', self::BILINGUAL)->toArray();
        self::assertSame('The enhanced accessibility mode', $article['title']);
        self::assertStringContainsString('agrandit le texte', self::text($article, 'html'));
        self::assertSame([['locale' => 'fr', 'slug' => 'le-mode-accessibilite-renforcee'], ['locale' => 'en', 'slug' => 'enhanced-accessibility-mode']], $article['alternates']);

        $tips = $this->get('/api/articles?locale=en&section=tips', self::BILINGUAL)->toArray();
        self::assertCount(2, $tips);
        self::assertSame(['slug' => 'tips', 'name' => 'Tips'], self::at($tips, 0, 'section'));
        self::assertSame([['slug' => 'tips', 'name' => 'Tips'], ['slug' => 'actualites', 'name' => 'Actualités']], $this->get('/api/article-sections?locale=en', self::BILINGUAL)->toArray());
    }

    public function testTranslatesTheContentsAndTheIdentity(): void
    {
        $page = $this->get('/api/legal_pages/mentions-legales?locale=en', self::BILINGUAL)->toArray();
        self::assertSame('Legal notice', $page['title']);
        self::assertSame([['locale' => 'fr', 'slug' => 'mentions-legales'], ['locale' => 'en', 'slug' => 'mentions-legales']], $page['alternates']);

        $metas = array_column($this->get('/api/page-metas?locale=en', self::BILINGUAL)->toArray(), 'description', 'page');
        self::assertSame(PageMetaFixtures::BLOG_DESCRIPTION_EN, $metas['blog']);

        $identity = $this->get('/api/site-identity?locale=en', self::BILINGUAL)->toArray();
        self::assertSame(['fr', 'en'], $identity['locales']);
        self::assertSame(SiteIdentity::DEFAULT_NAME, $identity['name']);
        self::assertSame(SiteIdentityFixtures::TAGLINE_EN, $identity['tagline']);
    }

    /**
     * Valeur imbriquée d'une réponse décodée.
     */
    private static function at(mixed $data, string|int ...$path): mixed
    {
        foreach ($path as $key) {
            self::assertIsArray($data);
            self::assertArrayHasKey($key, $data);
            $data = $data[$key];
        }

        return $data;
    }

    private static function text(mixed $data, string|int ...$path): string
    {
        $text = self::at($data, ...$path);
        self::assertIsString($text);

        return $text;
    }

    /**
     * @param array<string, string> $headers
     */
    private function get(string $url, array $headers = []): ResponseInterface
    {
        return static::createClient()->request('GET', $url, ['headers' => ['Accept' => 'application/json', ...$headers]]);
    }
}
