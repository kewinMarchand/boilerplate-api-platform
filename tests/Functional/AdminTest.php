<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Admin\Infrastructure\Security\AdminUser;
use App\Catalog\Domain\Category;
use App\Catalog\Domain\Product;
use App\Content\Domain\LegalPage;
use App\Site\Domain\SiteIdentity;
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

    public function testEditsTheSiteIdentityReadByTheApi(): void
    {
        $client = $this->loggedIn('admin-identite@example.fr');
        $logo = imagecreatetruecolor(800, 240);
        self::assertNotFalse($logo);
        imagealphablending($logo, false);
        imagesavealpha($logo, true);
        imagefill($logo, 0, 0, (int) imagecolorallocatealpha($logo, 0, 0, 0, 127));
        $path = sys_get_temp_dir().'/logo-oraki.png';
        imagepng($logo, $path);

        $client->request('GET', '/admin/site-identity');
        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('a.action-new');
        $client->submitForm('Enregistrer', [
            'site_identity[name]' => 'Oraki',
            'site_identity[tagline]' => 'Plantes rares et tropicales, livrées chez vous.',
            'site_identity[imageContent]' => $path,
            'site_identity[primaryColor]' => '#c2410c',
            'site_identity[secondaryColor]' => '#ffffff',
            'site_identity[defaultColorScheme]' => 'dark',
        ]);
        unlink($path);
        self::assertResponseRedirects('/admin/site-identity');

        try {
            $client->request('GET', '/api/site-identity', [], [], ['HTTP_ACCEPT' => 'application/json']);
            /** @var array{name: string, tagline: string, defaultColorScheme: string, logo: array{alt: string, width: int, height: int, src: string}, theme: array{light: array<string, string>}} $identity */
            $identity = json_decode((string) $client->getResponse()->getContent(), true);
            self::assertSame(['Oraki', 'Plantes rares et tropicales, livrées chez vous.', 'dark'], [$identity['name'], $identity['tagline'], $identity['defaultColorScheme']]);
            self::assertSame(['Oraki', 400, 120], [$identity['logo']['alt'], $identity['logo']['width'], $identity['logo']['height']]);
            self::assertMatchesRegularExpression('#^http://localhost:8090/media/[0-9a-f]{32}-200\.webp$#', $identity['logo']['src']);
            self::assertSame(['#c2410c', '#ffffff'], [$identity['theme']['light']['primary'], $identity['theme']['light']['on-primary']]);

            $client->request('GET', '/admin/site-identity');
            self::assertSelectorExists('[data-image-preview]:not([hidden]) img[src$="-200.webp"]');
            $client->submitForm('Enregistrer', ['site_identity[removeLogo]' => true, 'site_identity[primaryColor]' => '', 'site_identity[secondaryColor]' => '']);
            self::assertResponseRedirects('/admin/site-identity');
            $client->request('GET', '/api/site-identity', [], [], ['HTTP_ACCEPT' => 'application/json']);
            self::assertSame(['logo' => null, 'theme' => null], array_intersect_key((array) json_decode((string) $client->getResponse()->getContent(), true), ['logo' => true, 'theme' => true]));
        } finally {
            // La base de test est partagée : les tests de l'API lisent l'identité des fixtures
            $saved = $this->entityManager()->find(SiteIdentity::class, SiteIdentity::ID);
            self::assertInstanceOf(SiteIdentity::class, $saved);
            $default = SiteIdentity::byDefault();
            $saved->update($default->getName(), $default->getTagline(), null, null, $default->getDefaultColorScheme());
            $this->entityManager()->flush();
        }
    }

    public function testRefusesAPrimaryColorUnreadableOnTheLightBackground(): void
    {
        $client = $this->loggedIn('admin-contraste@example.fr');

        $client->request('GET', '/admin/site-identity');
        $client->submitForm('Enregistrer', ['site_identity[primaryColor]' => '#ffff00', 'site_identity[secondaryColor]' => '#ffffff']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#main form .alert-danger', 'La couleur principale doit contraster à 4,5:1 au moins avec le fond clair (actuellement 1,07:1).');
        self::assertNull($this->entityManager()->find(SiteIdentity::class, SiteIdentity::ID)?->getColors());
    }

    public function testRefusesASingleColor(): void
    {
        $client = $this->loggedIn('admin-couleur@example.fr');

        $client->request('GET', '/admin/site-identity');
        $client->submitForm('Enregistrer', ['site_identity[primaryColor]' => '#c2410c']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#main form .alert-danger', 'Saisir les deux couleurs, ou aucune.');
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
