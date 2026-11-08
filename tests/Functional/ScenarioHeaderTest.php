<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use ApiPlatform\Test\ApiTestCase;
use App\Site\Domain\BrandColors;
use App\Site\Domain\DefaultColorScheme;
use App\Site\Domain\HexColor;
use App\Site\Domain\SiteIdentity;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;

final class ScenarioHeaderTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = false;

    #[DataProvider('collections')]
    public function testErrorScenarioReturnsServerErrorAsProblemJson(string $uri): void
    {
        static::createClient()->request('GET', $uri, ['headers' => ['Accept' => 'application/json', 'X-Scenario' => 'error']]);

        self::assertResponseStatusCodeSame(500);
        self::assertResponseHeaderSame('content-type', 'application/problem+json');
    }

    #[DataProvider('collections')]
    public function testEmptyScenarioReturnsEmptyCollection(string $uri): void
    {
        static::createClient()->request('GET', $uri, ['headers' => ['Accept' => 'application/json', 'X-Scenario' => 'empty']]);

        self::assertResponseIsSuccessful();
        self::assertJsonEquals([]);
    }

    public function testErrorScenarioAppliesToProductFacets(): void
    {
        static::createClient()->request('GET', '/api/product-facets', ['headers' => ['Accept' => 'application/json', 'X-Scenario' => 'error']]);

        self::assertResponseStatusCodeSame(500);
    }

    public function testEmptyScenarioReturnsZeroFacets(): void
    {
        static::createClient()->request('GET', '/api/product-facets?category=feuillages', ['headers' => ['Accept' => 'application/json', 'X-Scenario' => 'empty']]);

        self::assertResponseIsSuccessful();
        self::assertJsonEquals([
            'totalItems' => 0,
            'exposure' => [['value' => 'soleil', 'count' => 0], ['value' => 'mi-ombre', 'count' => 0], ['value' => 'ombre', 'count' => 0]],
            'size' => [['value' => 'S', 'count' => 0], ['value' => 'M', 'count' => 0], ['value' => 'L', 'count' => 0]],
            'category' => [],
            'attributes' => [],
        ]);
    }

    public function testErrorScenarioAppliesToSiteIdentity(): void
    {
        static::createClient()->request('GET', '/api/site-identity', ['headers' => ['Accept' => 'application/json', 'X-Scenario' => 'error']]);

        self::assertResponseStatusCodeSame(500);
    }

    public function testEmptyScenarioReturnsTheDefaultSiteIdentity(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $identity = $entityManager->find(SiteIdentity::class, SiteIdentity::ID);
        self::assertInstanceOf(SiteIdentity::class, $identity);
        $identity->update('Oraki', '', null, new BrandColors(new HexColor('#c2410c'), new HexColor('#ffffff')), DefaultColorScheme::Dark, null, null);
        $entityManager->flush();

        try {
            $client->request('GET', '/api/site-identity', ['headers' => ['Accept' => 'application/json', 'X-Scenario' => 'empty']]);

            self::assertResponseIsSuccessful();
            self::assertJsonEquals(SiteIdentityApiTest::DEFAULT);
        } finally {
            // La base de test est partagée : les autres tests lisent l'identité des fixtures
            $entityManager = static::getContainer()->get(EntityManagerInterface::class);
            self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
            $identity = $entityManager->find(SiteIdentity::class, SiteIdentity::ID);
            self::assertInstanceOf(SiteIdentity::class, $identity);
            $default = SiteIdentity::byDefault();
            $identity->update($default->getName(), $default->getTagline(), null, null, $default->getDefaultColorScheme(), null, null);
            $entityManager->flush();
        }
    }

    public function testErrorScenarioAppliesToContactForm(): void
    {
        static::createClient()->request('POST', '/api/contact_messages', [
            'headers' => ['Accept' => 'application/json', 'X-Scenario' => 'error'],
            'json' => ['name' => 'Camille', 'email' => 'camille@example.fr', 'message' => 'Un message assez long.'],
        ]);

        self::assertResponseStatusCodeSame(500);
    }

    public function testUnknownScenarioIsIgnored(): void
    {
        $response = static::createClient()->request('GET', '/api/tasks', ['headers' => ['Accept' => 'application/json', 'X-Scenario' => 'inconnu']]);

        self::assertResponseIsSuccessful();
        self::assertCount(3, $response->toArray());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function collections(): iterable
    {
        yield 'tâches' => ['/api/tasks'];
        yield 'articles' => ['/api/articles'];
        yield 'catégories' => ['/api/categories'];
        yield 'produits' => ['/api/products'];
        yield 'produits à la une' => ['/api/featured-products'];
        yield 'articles filtrés' => ['/api/articles?section=conseils'];
        yield 'rubriques' => ['/api/article-sections'];
        yield 'formats' => ['/api/article-formats'];
        yield 'référencement des pages fixes' => ['/api/page-metas'];
    }
}
