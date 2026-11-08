<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use ApiPlatform\Test\ApiTestCase;
use App\Contact\Domain\ContactMessage;
use App\Tests\Support\FailingMailer;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class ContactMessageApiTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = false;

    private const string NAME_ERROR = 'Indiquez votre nom (2 caractères minimum).';
    private const string EMAIL_ERROR = 'Indiquez une adresse e-mail valide, par exemple nom@domaine.fr.';
    private const string MESSAGE_ERROR = 'Votre message doit contenir au moins 10 caractères.';

    public function testCreatesAndPersistsMessage(): void
    {
        static::createClient()->request('POST', '/api/contact_messages', [
            'headers' => ['Accept' => 'application/json'],
            'json' => [
                'name' => '  Camille Martin ',
                'email' => 'camille.martin@example.fr',
                'message' => 'Bonjour, je souhaite un devis pour une refonte.',
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertJsonContains([
            'name' => 'Camille Martin',
            'email' => 'camille.martin@example.fr',
            'message' => 'Bonjour, je souhaite un devis pour une refonte.',
        ]);

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $saved = $entityManager->getRepository(ContactMessage::class)->findOneBy([], ['id' => 'DESC']);
        self::assertNotNull($saved);
        self::assertSame('Camille Martin', $saved->getName());
    }

    public function testSendsAContactMessageByMail(): void
    {
        $this->post(['name' => 'Camille Martin', 'email' => 'camille.martin@example.fr', 'message' => 'Bonjour, je souhaite un devis.']);

        self::assertResponseStatusCodeSame(201);
        self::assertNull($this->lastMessage()->getProductSlug());
        self::assertEmailCount(1);
        $email = self::getMailerMessage();
        self::assertInstanceOf(Email::class, $email);
        self::assertSame('Message de contact : Camille Martin', $email->getSubject());
        self::assertSame(['no-reply@example.com', 'contact@example.com', 'camille.martin@example.fr'], [
            $email->getFrom()[0]->getAddress(),
            $email->getTo()[0]->getAddress(),
            $email->getReplyTo()[0]->getAddress(),
        ]);
        self::assertSame("Nom : Camille Martin\nAdresse : camille.martin@example.fr\n\nMessage :\nBonjour, je souhaite un devis.\n", $email->getTextBody());
        self::assertNull($email->getHtmlBody());
    }

    public function testSendsAnInquiryAboutAProductByMail(): void
    {
        $this->post(['name' => 'Camille Martin', 'email' => 'camille.martin@example.fr', 'message' => 'Est-il livré en pot ? <b>Merci</b>', 'product' => 'monstera-deliciosa']);

        self::assertResponseStatusCodeSame(201);
        self::assertJsonContains(['product' => 'monstera-deliciosa']);
        self::assertSame('monstera-deliciosa', $this->lastMessage()->getProductSlug());
        $email = self::getMailerMessage();
        self::assertInstanceOf(Email::class, $email);
        self::assertSame('Demande de renseignement : Monstera deliciosa', $email->getSubject());
        self::assertSame("Nom : Camille Martin\nAdresse : camille.martin@example.fr\nProduit : Monstera deliciosa (monstera-deliciosa)\n\nMessage :\nEst-il livré en pot ? <b>Merci</b>\n", $email->getTextBody());
    }

    public function testRejectsAnUnknownProduct(): void
    {
        $before = $this->lastMessage()->getId();
        $response = $this->post(['name' => 'Camille Martin', 'email' => 'camille.martin@example.fr', 'message' => 'Est-il livré en pot ?', 'product' => 'produit-inconnu']);

        self::assertResponseStatusCodeSame(422);
        /** @var array{violations: list<array{propertyPath: string, message: string}>} $body */
        $body = $response->toArray(false);
        self::assertSame(['product' => 'Ce produit n\'existe pas.'], array_column($body['violations'], 'message', 'propertyPath'));
        self::assertSame($before, $this->lastMessage()->getId());
        self::assertEmailCount(0);
    }

    public function testKeepsTheMessageAndAnswers201WhenTheMailCannotBeSent(): void
    {
        FailingMailer::$failing = true;

        try {
            $this->post(['name' => 'Camille Martin', 'email' => 'camille.martin@example.fr', 'message' => 'Message reçu malgré la panne.', 'product' => 'monstera-deliciosa']);
        } finally {
            FailingMailer::$failing = false;
        }

        self::assertResponseStatusCodeSame(201);
        self::assertSame('Message reçu malgré la panne.', $this->lastMessage()->getMessage());
        self::assertEmailCount(0);
    }

    /**
     * @param array<string, string> $payload
     * @param array<string, string> $expected
     */
    #[DataProvider('invalidPayloads')]
    public function testRejectsInvalidMessageWithFrenchViolations(array $payload, array $expected): void
    {
        $response = static::createClient()->request('POST', '/api/contact_messages', [
            'headers' => ['Accept' => 'application/json'],
            'json' => $payload,
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertResponseHeaderSame('content-type', 'application/problem+json');

        /** @var array{violations: list<array{propertyPath: string, message: string}>} $body */
        $body = $response->toArray(false);
        $violations = array_column($body['violations'], 'message', 'propertyPath');
        self::assertSame($expected, $violations);
    }

    /**
     * @return iterable<string, array{array<string, string>, array<string, string>}>
     */
    public static function invalidPayloads(): iterable
    {
        yield 'champs absents' => [
            [],
            ['name' => self::NAME_ERROR, 'email' => self::EMAIL_ERROR, 'message' => self::MESSAGE_ERROR],
        ];
        yield 'champs vides ou trop courts' => [
            ['name' => ' A ', 'email' => '', 'message' => '  Court   '],
            ['name' => self::NAME_ERROR, 'email' => self::EMAIL_ERROR, 'message' => self::MESSAGE_ERROR],
        ];
        yield 'champs trop longs' => [
            ['name' => str_repeat('a', 256), 'email' => str_repeat('a', 250).'@exemple.fr', 'message' => str_repeat('a', 5001)],
            [
                'name' => 'Votre nom ne doit pas dépasser 255 caractères.',
                'email' => 'Votre adresse e-mail ne doit pas dépasser 255 caractères.',
                'message' => 'Votre message ne doit pas dépasser 5000 caractères.',
            ],
        ];
        yield 'e-mail invalide seul' => [
            ['name' => 'Camille', 'email' => 'camille@', 'message' => 'Un message assez long.'],
            ['email' => self::EMAIL_ERROR],
        ];
    }

    /**
     * @param array<string, string> $payload
     */
    private function post(array $payload): ResponseInterface
    {
        return static::createClient()->request('POST', '/api/contact_messages', [
            'headers' => ['Accept' => 'application/json'],
            'json' => $payload,
        ]);
    }

    private function lastMessage(): ContactMessage
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->clear();
        $message = $entityManager->getRepository(ContactMessage::class)->findOneBy([], ['id' => 'DESC']);
        self::assertNotNull($message);

        return $message;
    }
}
