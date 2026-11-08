<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use ApiPlatform\Test\ApiTestCase;
use App\Media\Domain\ImageUsage;
use App\Tests\Support\TestImages;

final class ProductDetailApiTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = false;

    public function testReadsAProductBySlugWithItsCategoryPath(): void
    {
        $response = static::createClient()->request('GET', '/api/products/monstera-deliciosa', ['headers' => ['Accept' => 'application/json']]);

        self::assertResponseIsSuccessful();
        self::assertSame([
            'id' => 1,
            'slug' => 'monstera-deliciosa',
            'name' => 'Monstera deliciosa',
            'categorySlug' => 'monstera',
            'price' => 3490,
            'exposure' => 'mi-ombre',
            'size' => 'M',
            'inStock' => true,
            'image' => TestImages::demoView('product-1', ImageUsage::Product, 'Monstera deliciosa'),
            'categoryPath' => [
                ['slug' => 'plantes-interieur', 'name' => 'Plantes d’intérieur'],
                ['slug' => 'feuillages', 'name' => 'Feuillages'],
                ['slug' => 'monstera', 'name' => 'Monstera'],
            ],
            'description' => "Grandes feuilles vert profond, découpées et perforées avec l’âge : le monstera deliciosa habille une pièce à lui seul.\n\nLumière vive sans soleil direct, arrosage quand le terreau a séché sur deux centimètres. Un tuteur en mousse l’aide à grimper.",
        ], $response->toArray());
    }

    public function testAnswers404ForAnUnknownSlug(): void
    {
        static::createClient()->request('GET', '/api/products/inconnu', ['headers' => ['Accept' => 'application/json']]);

        self::assertResponseStatusCodeSame(404);
    }

    public function testListsTheFeaturedProductsByName(): void
    {
        $response = static::createClient()->request('GET', '/api/featured-products', ['headers' => ['Accept' => 'application/json']]);

        self::assertResponseIsSuccessful();
        /** @var list<array{slug: string}> $products */
        $products = $response->toArray();
        self::assertSame(
            ['anthurium-andreanum-rouge', 'calliandra-haematocephala', 'heliconia-rostrata', 'nenuphar-bleu-du-nil', 'strelitzia-reginae'],
            array_column($products, 'slug'),
        );
        self::assertSame(
            ['id', 'slug', 'name', 'categorySlug', 'price', 'exposure', 'size', 'inStock', 'image'],
            array_keys($products[0]),
        );
    }
}
