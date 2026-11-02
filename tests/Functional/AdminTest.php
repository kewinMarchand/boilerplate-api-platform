<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Admin\Infrastructure\Security\AdminUser;
use App\Catalog\Domain\Category;
use App\Catalog\Domain\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminTest extends WebTestCase
{
    private const string PASSWORD = 'un-mot-de-passe-de-test';

    protected function setUp(): void
    {
        // Le compteur des tentatives de connexion vit dans le cache : un lancement précédent ne doit pas bloquer celui-ci
        $rateLimiterCache = static::getContainer()->get('cache.rate_limiter');
        self::assertInstanceOf(\Psr\Cache\CacheItemPoolInterface::class, $rateLimiterCache);
        $rateLimiterCache->clear();
        self::ensureKernelShutdown();
    }

    public function testRedirectsAnonymousVisitorsToTheLoginPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        self::assertResponseRedirects('/admin/login');
    }

    public function testLogsInAndListsTheCatalog(): void
    {
        $client = $this->loggedIn('admin-liste@example.fr');
        $client->request('GET', '/admin');
        $client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Produits');
    }

    public function testCreatesAProductThroughTheForm(): void
    {
        $client = $this->loggedIn('admin-produit@example.fr');
        $category = $this->entityManager()->getRepository(Category::class)->findOneBy([]);
        self::assertInstanceOf(Category::class, $category);

        $client->request('GET', '/admin/product/new');
        self::assertResponseIsSuccessful();
        $client->submitForm('Enregistrer', [
            'product[name]' => 'Palmier de test',
            'product[slug]' => 'palmier-de-test',
            'product[categoryId]' => (string) $category->getId(),
            'product[price]' => '12,50',
            'product[image]' => '3',
        ]);

        self::assertResponseRedirects();
        $saved = $this->entityManager()->getRepository(Product::class)->findOneBy(['slug' => 'palmier-de-test']);
        self::assertInstanceOf(Product::class, $saved);
        self::assertSame(1250, $saved->getPrice()->cents);

        // La base de test est partagée : les tests de l'API comptent les 24 produits des fixtures
        $this->entityManager()->remove($saved);
        $this->entityManager()->flush();
    }

    public function testShowsACatalogRuleViolationOnTheForm(): void
    {
        $client = $this->loggedIn('admin-regle@example.fr');
        $taken = $this->entityManager()->getRepository(Category::class)->findOneBy([]);
        self::assertInstanceOf(Category::class, $taken);

        $client->request('GET', '/admin/category/new');
        $client->submitForm('Enregistrer', ['category[name]' => 'Doublon', 'category[slug]' => $taken->getSlug(), 'category[position]' => '1']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#main form .alert-danger', \sprintf('Le slug « %s » est déjà pris par une autre catégorie.', $taken->getSlug()));
    }

    public function testThrottlesRepeatedFailedLogins(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $this->createAdmin('admin-limite@example.fr');

        for ($attempt = 1; $attempt <= 5; ++$attempt) {
            $this->submitLogin($client, 'admin-limite@example.fr', 'mauvais-mot-de-passe');
        }
        $this->submitLogin($client, 'admin-limite@example.fr', self::PASSWORD);
        $client->followRedirect();

        self::assertRouteSame('admin_login');
        self::assertSelectorExists('.alert-danger');
    }

    private function loggedIn(string $email): KernelBrowser
    {
        $client = static::createClient();
        $this->createAdmin($email);
        $this->submitLogin($client, $email, self::PASSWORD);
        self::assertResponseRedirects('/admin');

        return $client;
    }

    private function submitLogin(KernelBrowser $client, string $email, string $password): void
    {
        $client->request('GET', '/admin/login');
        $client->submitForm('Se connecter', ['_username' => $email, '_password' => $password]);
    }

    private function createAdmin(string $email): void
    {
        $entityManager = $this->entityManager();
        if (null !== $entityManager->getRepository(AdminUser::class)->findOneBy(['email' => $email])) {
            return;
        }
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertInstanceOf(UserPasswordHasherInterface::class, $hasher);
        $user = new AdminUser($email, '');
        $user->changePassword($hasher->hashPassword($user, self::PASSWORD));
        $entityManager->persist($user);
        $entityManager->flush();
    }

    private function entityManager(): EntityManagerInterface
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        return $entityManager;
    }
}
