<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use ApiPlatform\Test\ApiTestCase;
use App\Tests\Support\RecordingSentryHttpClient;

/**
 * Envoi des erreurs vers GlitchTip, par le client HTTP factice déclaré en test.
 * La 500 inattendue est provoquée par une base injoignable (port 1).
 */
final class ErrorReportingTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = false;

    private const string FAKE_DSN = 'http://cle@glitchtip.test/1';
    private const string UNREACHABLE_DATABASE = 'postgresql://app:app@127.0.0.1:1/app?serverVersion=17&charset=utf8';

    /** @var array<string, string|null> */
    private array $savedEnv = [];

    protected function setUp(): void
    {
        RecordingSentryHttpClient::$requests = [];
    }

    protected function tearDown(): void
    {
        foreach ($this->savedEnv as $name => $value) {
            if (null === $value) {
                unset($_ENV[$name], $_SERVER[$name]);
            } else {
                $_ENV[$name] = $_SERVER[$name] = $value;
            }
        }
        $this->savedEnv = [];

        parent::tearDown();
    }

    public function testNothingIsSentWithoutDsn(): void
    {
        $this->setEnv('GLITCHTIP_DSN', '');
        $this->setEnv('DATABASE_URL', self::UNREACHABLE_DATABASE);

        static::createClient()->request('GET', '/api/tasks', ['headers' => ['Accept' => 'application/json']]);

        self::assertResponseStatusCodeSame(500);
        self::assertSame([], RecordingSentryHttpClient::$requests);
    }

    public function testUnexpectedServerErrorIsSent(): void
    {
        $this->setEnv('GLITCHTIP_DSN', self::FAKE_DSN);
        $this->setEnv('DATABASE_URL', self::UNREACHABLE_DATABASE);

        static::createClient()->request('GET', '/api/tasks', ['headers' => ['Accept' => 'application/json']]);

        self::assertResponseStatusCodeSame(500);
        self::assertCount(1, RecordingSentryHttpClient::$requests);
    }

    public function testValidationErrorIsNotSent(): void
    {
        $this->setEnv('GLITCHTIP_DSN', self::FAKE_DSN);

        static::createClient()->request('POST', '/api/contact_messages', [
            'headers' => ['Accept' => 'application/json'],
            'json' => ['name' => 'A', 'email' => 'camille@', 'message' => 'Court'],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame([], RecordingSentryHttpClient::$requests);
    }

    private function setEnv(string $name, string $value): void
    {
        if (!\array_key_exists($name, $this->savedEnv)) {
            $previous = $_ENV[$name] ?? $_SERVER[$name] ?? null;
            $this->savedEnv[$name] = \is_string($previous) ? $previous : null;
        }
        $_ENV[$name] = $_SERVER[$name] = $value;
    }
}
