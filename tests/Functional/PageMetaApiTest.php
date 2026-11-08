<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use ApiPlatform\Test\ApiTestCase;
use App\Content\Infrastructure\Doctrine\Fixtures\PageMetaFixtures;

final class PageMetaApiTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = false;

    public function testListsEveryFixedPageWithEmptyValuesExceptTheBlogDescription(): void
    {
        static::createClient()->request('GET', '/api/page-metas', ['headers' => ['Accept' => 'application/json']]);

        self::assertResponseIsSuccessful();
        self::assertJsonEquals(array_map(
            static fn (string $page): array => ['page' => $page, 'title' => '', 'description' => 'blog' === $page ? PageMetaFixtures::BLOG_DESCRIPTION : ''],
            ['home', 'catalog', 'blog', 'contact', 'sitemap', 'legal-notice', 'privacy', 'accessibility'],
        ));
    }

    public function testTheBlogDescriptionFitsTheLengthOfTheContract(): void
    {
        self::assertLessThanOrEqual(160, mb_strlen(PageMetaFixtures::BLOG_DESCRIPTION));
    }

    public function testIsReadOnly(): void
    {
        static::createClient()->request('POST', '/api/page-metas', ['json' => ['page' => 'home']]);

        self::assertResponseStatusCodeSame(405);
    }
}
