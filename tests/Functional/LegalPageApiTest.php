<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

final class LegalPageApiTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = false;

    public function testReadsALegalPageSplitIntoSections(): void
    {
        static::createClient()->request('GET', '/api/legal_pages/mentions-legales', ['headers' => ['Accept' => 'application/json']]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'slug' => 'mentions-legales',
            'title' => 'Mentions légales',
            'introduction' => '<p>Les informations ci-dessous sont des valeurs d\'exemple, fictives, à remplacer depuis l\'administration.</p>',
            'sections' => [
                [
                    'title' => 'Éditeur',
                    'html' => "<p>Exemple SAS (société fictive), 1 rue de l’Exemple, 13000 Marseille. SIRET : 000 000 000 00000.</p>\n<p>Contact : <a href=\"mailto:contact@example.com\">contact@example.com</a></p>",
                ],
                ['title' => 'Directeur de la publication'],
                ['title' => 'Hébergeur'],
                ['title' => 'Propriété intellectuelle'],
            ],
        ]);
    }

    public function testAnswers404ForAnUnknownPage(): void
    {
        static::createClient()->request('GET', '/api/legal_pages/cgv', ['headers' => ['Accept' => 'application/json']]);

        self::assertResponseStatusCodeSame(404);
    }

    public function testIsReadOnly(): void
    {
        static::createClient()->request('PUT', '/api/legal_pages/mentions-legales', ['json' => ['title' => 'Autre titre']]);

        self::assertResponseStatusCodeSame(405);
    }
}
