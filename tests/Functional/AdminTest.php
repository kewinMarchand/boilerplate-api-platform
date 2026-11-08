<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Admin\Infrastructure\Security\AdminUser;
use App\Catalog\Domain\Category;
use App\Catalog\Domain\Product;
use App\Content\Domain\LegalPage;
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

    public function testShowsTheListsAndRecordsOfEachSection(): void
    {
        $client = $this->loggedIn('admin-pages@example.fr');
        foreach (['category', 'product', 'article', 'legal-page'] as $section) {
            $crawler = $client->request('GET', \sprintf('/admin/%s', $section));
            self::assertResponseIsSuccessful($section);
            $client->click($crawler->filter('a.action-detail')->first()->link());
            self::assertResponseIsSuccessful($section);
        }
    }

    public function testShowsTheFormattedColumnsOfTheCatalog(): void
    {
        $client = $this->loggedIn('admin-colonnes@example.fr');
        foreach (['category', 'product'] as $section) {
            $crawler = $client->request('GET', \sprintf('/admin/%s', $section));
            self::assertStringNotContainsString('Inaccessible', $crawler->filter('body')->text(), $section);
            $crawler = $client->click($crawler->filter('a.action-detail')->first()->link());
            self::assertStringNotContainsString('Inaccessible', $crawler->filter('body')->text(), $section);
        }
        self::assertSelectorTextContains('body', '€');
    }

    public function testShowsTheCurrentImageInTheListsAndTheForm(): void
    {
        $client = $this->loggedIn('admin-apercu@example.fr');
        $product = $this->entityManager()->getRepository(Product::class)->findOneBy([], ['id' => 'ASC']);
        self::assertInstanceOf(Product::class, $product);
        $image = $product->getImage();
        self::assertNotNull($image);
        $preview = \sprintf('/media/%s-400.webp', $image->key);

        $client->request('GET', '/admin/product');
        self::assertSelectorExists(\sprintf('img[src="%s"]', $preview));

        $client->request('GET', \sprintf('/admin/product/%d/edit', $product->getId()));
        self::assertSelectorExists(\sprintf('[data-image-preview] img[src="%s"]', $preview));
        self::assertSelectorTextContains('[data-image-preview] figcaption', 'Image actuelle');

        $client->request('GET', '/admin/product/new');
        self::assertSelectorExists('[data-image-preview][hidden]');
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
            'product[featured]' => true,
            'product[description]' => "Rustique.\r\n\r\nRésiste au vent.",
            'product[imageContent]' => \dirname(__DIR__, 2).'/fixtures/images/product-2.webp',
        ]);

        self::assertResponseRedirects();
        $saved = $this->entityManager()->getRepository(Product::class)->findOneBy(['slug' => 'palmier-de-test']);
        self::assertInstanceOf(Product::class, $saved);
        self::assertSame(1250, $saved->getPrice()->cents);
        self::assertTrue($saved->isFeatured());
        self::assertSame("Rustique.\n\nRésiste au vent.", $saved->getDescription());
        self::assertSame('Palmier de test', $saved->getImage()?->alt);
        self::assertFileExists(\dirname(__DIR__, 2).'/var/test-media/'.$saved->getImage()->key.'-800.avif');

        // La base de test est partagée : les tests de l'API comptent les 24 produits des fixtures
        $this->entityManager()->remove($saved);
        $this->entityManager()->flush();
    }

    public function testRefusesAnImageTooSmallForAnArticle(): void
    {
        $client = $this->loggedIn('admin-image@example.fr');
        $small = imagecreatetruecolor(640, 360);
        self::assertNotFalse($small);
        $path = sys_get_temp_dir().'/petite-illustration.png';
        imagepng($small, $path);

        $client->request('GET', '/admin/article/new');
        $client->submitForm('Enregistrer', [
            'article[title]' => 'Trop petite',
            'article[slug]' => 'trop-petite',
            'article[publishedAt]' => '2026-11-07',
            'article[imageContent]' => $path,
            'article[imageAlt]' => 'Une photo',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#main form .alert-danger', 'Recadrée au format 16:9, l\'image doit faire au moins 960 × 540 px : elle fait 640 × 360 px.');
        unlink($path);
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

    public function testEditsALegalPageReadByTheApi(): void
    {
        $client = $this->loggedIn('admin-page@example.fr');
        $page = $this->entityManager()->getRepository(LegalPage::class)->findOneBy(['slug' => 'mentions-legales']);
        self::assertInstanceOf(LegalPage::class, $page);
        $body = $page->getBody();

        $client->request('GET', \sprintf('/admin/legal-page/%d/edit', $page->getId()));
        $client->submitForm('Enregistrer', ['legal_page[title]' => 'Mentions légales', 'legal_page[body]' => "## Éditeur\n\nNouvel éditeur."]);
        self::assertResponseRedirects();

        $client->request('GET', '/api/legal_pages/mentions-legales', [], [], ['HTTP_ACCEPT' => 'application/json']);
        self::assertJsonStringEqualsJsonString(
            (string) json_encode(['slug' => 'mentions-legales', 'title' => 'Mentions légales', 'introduction' => '', 'sections' => [['title' => 'Éditeur', 'html' => '<p>Nouvel éditeur.</p>']]]),
            (string) $client->getResponse()->getContent(),
        );

        // La base de test est partagée : les tests de l'API lisent le texte des fixtures
        $saved = $this->entityManager()->getRepository(LegalPage::class)->findOneBy(['slug' => 'mentions-legales']);
        self::assertInstanceOf(LegalPage::class, $saved);
        $saved->update('Mentions légales', $body);
        $this->entityManager()->flush();
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
