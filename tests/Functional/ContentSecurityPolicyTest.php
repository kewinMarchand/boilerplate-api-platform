<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use ApiPlatform\Test\ApiTestCase;
use App\Shared\Infrastructure\Http\ContentSecurityPolicySubscriber;

final class ContentSecurityPolicyTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = false;

    public function testJsonResponseCarriesThePolicy(): void
    {
        static::createClient()->request('GET', '/api/tasks', ['headers' => ['Accept' => 'application/json']]);

        self::assertResponseHeaderSame('content-security-policy', ContentSecurityPolicySubscriber::POLICY);
    }

    public function testHtmlDocumentationIsLeftAlone(): void
    {
        static::createClient()->request('GET', '/api/docs', ['headers' => ['Accept' => 'text/html']]);

        self::assertResponseHeaderSame('content-type', 'text/html; charset=UTF-8');
        self::assertResponseNotHasHeader('content-security-policy');
    }
}
