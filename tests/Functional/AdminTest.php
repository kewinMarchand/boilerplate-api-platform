<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Admin\Infrastructure\Security\AdminUser;
use App\Blog\Domain\Article;
use App\Blog\Domain\ArticleTerm;
use App\Blog\Domain\ArticleTermKind;
use App\Catalog\Domain\AttributeChoice;
use App\Catalog\Domain\Category;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductAttribute;
use App\Contact\Domain\ContactMessage;
use App\Content\Domain\LegalPage;
use App\Content\Domain\MetaPage;
use App\Content\Domain\PageMeta;
use App\Media\Domain\Image;
use App\Site\Domain\DefaultColorScheme;
use App\Site\Domain\SiteIdentity;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
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
        foreach (['category', 'product', 'product-attribute', 'article', 'article-section', 'article-format', 'legal-page', 'page-meta'] as $section) {
            $crawler = $client->request('GET', \sprintf('/admin/%s', $section));
            self::assertResponseIsSuccessful($section);
            $client->click($crawler->filter('a.action-detail')->first()->link());
            self::assertResponseIsSuccessful($section);
        }
    }

    public function testShowsTheFormattedColumnsOfTheCatalog(): void
    {
        $client = $this->loggedIn('admin-colonnes@example.fr');
        foreach (['category', 'product-attribute', 'product'] as $section) {
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
            'product[attributes][feuillage]' => 'caduc',
            'product[attributes][hauteur-adulte]' => '12.5',
        ]);

        self::assertResponseRedirects();
        $saved = $this->entityManager()->getRepository(Product::class)->findOneBy(['slug' => 'palmier-de-test']);
        self::assertInstanceOf(Product::class, $saved);
        self::assertSame(1250, $saved->getPrice()->cents);
        self::assertTrue($saved->isFeatured());
        self::assertSame("Rustique.\n\nRésiste au vent.", $saved->getDescription());
        self::assertSame('Palmier de test', $saved->getImage()?->alt);
        self::assertFileExists(\dirname(__DIR__, 2).'/var/test-media/'.$saved->getImage()->key.'-800.avif');
        self::assertSame(['feuillage' => 'caduc', 'hauteur-adulte' => '12.5'], $saved->getAttributeValues());

        // La base de test est partagée : les tests de l'API comptent les 24 produits des fixtures
        $this->entityManager()->remove($saved);
        $this->entityManager()->flush();
    }

    public function testCreatesAndEditsAnAttributeThroughTheForm(): void
    {
        $client = $this->loggedIn('admin-attribut@example.fr');

        $client->request('GET', '/admin/product-attribute/new');
        self::assertResponseIsSuccessful();
        $client->submitForm('Enregistrer', [
            'product_attribute[code]' => 'port',
            'product_attribute[label]' => 'Port',
            'product_attribute[type]' => 'choice',
            'product_attribute[filterable]' => true,
            'product_attribute[position]' => '3',
            'product_attribute[choices][0][value]' => 'buissonnant',
            'product_attribute[choices][0][label]' => 'Buissonnant',
        ]);
        self::assertResponseRedirects();
        $saved = $this->entityManager()->getRepository(ProductAttribute::class)->findOneBy(['code' => 'port']);
        self::assertInstanceOf(ProductAttribute::class, $saved);

        try {
            $client->request('GET', \sprintf('/admin/product-attribute/%d/edit', $saved->getId()));
            self::assertSelectorExists('input[name="product_attribute[code]"][disabled]');
            $client->submitForm('Enregistrer', [
                'product_attribute[label]' => 'Port de la plante',
                'product_attribute[choices][1][value]' => 'retombant',
                'product_attribute[choices][1][label]' => 'Retombant',
            ]);
            self::assertResponseRedirects();

            $this->entityManager()->clear();
            $edited = $this->entityManager()->find(ProductAttribute::class, $saved->getId());
            self::assertInstanceOf(ProductAttribute::class, $edited);
            self::assertSame(['port', 'Port de la plante'], [$edited->getCode(), $edited->getLabel()]);
            self::assertEquals([new AttributeChoice('buissonnant', 'Buissonnant'), new AttributeChoice('retombant', 'Retombant')], $edited->getChoices());

            $client->request('GET', '/admin/product/new');
            self::assertSelectorTextContains('label[for="product_attributes_port"]', 'Port de la plante');
        } finally {
            // La base de test est partagée : les tests de l'API comptent les attributs des fixtures
            $attribute = $this->entityManager()->getRepository(ProductAttribute::class)->findOneBy(['code' => 'port']);
            if (null !== $attribute) {
                $this->entityManager()->remove($attribute);
                $this->entityManager()->flush();
            }
        }
    }

    /**
     * @param class-string|null    $entity   fiche modifiée, la première trouvée ; null : page sans identifiant
     * @param array<string, mixed> $criteria
     */
    #[DataProvider('requiredFields')]
    public function testShowsAValidationErrorWhenARequiredFieldIsEmptied(string $section, ?string $entity, array $criteria, string $field, string $value = ''): void
    {
        $client = $this->loggedIn('admin-champ-vide@example.fr');
        $url = \sprintf('/admin/%s', $section);
        if (null !== $entity) {
            $record = $this->entityManager()->getRepository($entity)->findOneBy($criteria, ['id' => 'ASC']);
            self::assertNotNull($record);
            self::assertTrue(method_exists($record, 'getId'));
            $id = $record->getId();
            self::assertIsInt($id);
            $url = \sprintf('/admin/%s/%d/edit', $section, $id);
        }

        $client->request('GET', $url);
        $client->submitForm('Enregistrer', [$field => $value]);

        self::assertResponseStatusCodeSame(422);
        $fieldId = str_replace(['][', '[', ']'], ['_', '_', ''], $field);
        self::assertSelectorTextContains(\sprintf('#%s ~ .invalid-feedback, #%s + .invalid-feedback', $fieldId, $fieldId), 'Cette valeur ne doit pas être vide.');
    }

    /**
     * Un champ texte vidé, ou rempli d'espaces que le formulaire retire, arrive à null sans `empty_data` : la propriété
     * typée `string` de l'objet de saisie le refuse (500) avant toute validation.
     *
     * @return iterable<string, array{0: string, 1: class-string|null, 2: array<string, mixed>, 3: string, 4?: string}>
     */
    public static function requiredFields(): iterable
    {
        yield 'nom du produit' => ['product', Product::class, [], 'product[name]'];
        yield 'nom du produit en espaces seuls' => ['product', Product::class, [], 'product[name]', '   '];
        yield 'slug du produit' => ['product', Product::class, [], 'product[slug]'];
        yield 'nom de la catégorie' => ['category', Category::class, [], 'category[name]'];
        yield 'slug de la catégorie' => ['category', Category::class, [], 'category[slug]'];
        yield 'code de l\'attribut' => ['product-attribute/new', null, [], 'product_attribute[code]'];
        yield 'libellé de l\'attribut' => ['product-attribute', ProductAttribute::class, [], 'product_attribute[label]'];
        yield 'titre de l\'article' => ['article', Article::class, [], 'article[title]'];
        yield 'slug de l\'article' => ['article', Article::class, [], 'article[slug]'];
        yield 'nom de la rubrique' => ['article-section', ArticleTerm::class, ['kind' => ArticleTermKind::Section], 'article_term[name]'];
        yield 'slug du format' => ['article-format', ArticleTerm::class, ['kind' => ArticleTermKind::Format], 'article_term[slug]'];
        yield 'titre de la page légale' => ['legal-page', LegalPage::class, [], 'legal_page[title]'];
        yield 'texte de la page légale' => ['legal-page', LegalPage::class, [], 'legal_page[body]'];
        yield 'nom du site' => ['site-identity', null, [], 'site_identity[name]'];
        yield 'adresse des messages' => ['site-identity', null, [], 'site_identity[contactRecipient]'];
    }

    public function testRefusesToRemoveAnAttributeValueCarriedByProducts(): void
    {
        $client = $this->loggedIn('admin-valeur@example.fr');
        $foliage = $this->entityManager()->getRepository(ProductAttribute::class)->findOneBy(['code' => 'feuillage']);
        self::assertInstanceOf(ProductAttribute::class, $foliage);

        $client->request('GET', \sprintf('/admin/product-attribute/%d/edit', $foliage->getId()));
        $client->submitForm('Enregistrer', ['product_attribute[choices][0][value]' => '', 'product_attribute[choices][0][label]' => '']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#main form .alert-danger', 'La valeur « Persistant » est utilisée par 6 produits.');
    }

    public function testRefusesATakenAttributeCode(): void
    {
        $client = $this->loggedIn('admin-code@example.fr');

        $client->request('GET', '/admin/product-attribute/new');
        $client->submitForm('Enregistrer', ['product_attribute[code]' => 'feuillage', 'product_attribute[label]' => 'Doublon', 'product_attribute[type]' => 'text', 'product_attribute[position]' => '9']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#main form .alert-danger', 'Le code « feuillage » est déjà pris par un autre attribut.');
    }

    public function testRefusesAnAttributeValueOnTheProductForm(): void
    {
        $client = $this->loggedIn('admin-valeur-produit@example.fr');
        $product = $this->entityManager()->getRepository(Product::class)->findOneBy(['slug' => 'palmier-nain']);
        self::assertInstanceOf(Product::class, $product);

        $client->request('GET', \sprintf('/admin/product/%d/edit', $product->getId()));
        self::assertSelectorExists('input[name="product[attributes][hauteur-adulte]"][value="250"]');
        $client->submitForm('Enregistrer', ['product[attributes][hauteur-adulte]' => '-']);

        self::assertResponseStatusCodeSame(422);
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
            (string) json_encode(['slug' => 'mentions-legales', 'title' => 'Mentions légales', 'introduction' => '', 'sections' => [['title' => 'Éditeur', 'html' => '<p>Nouvel éditeur.</p>']], 'alternates' => []]),
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
            'site_identity[useLibraryColors]' => false,
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
            $client->submitForm('Enregistrer', ['site_identity[removeLogo]' => true, 'site_identity[useLibraryColors]' => true]);
            self::assertResponseRedirects('/admin/site-identity');
            $client->request('GET', '/api/site-identity', [], [], ['HTTP_ACCEPT' => 'application/json']);
            self::assertSame(['logo' => null, 'theme' => null], array_intersect_key((array) json_decode((string) $client->getResponse()->getContent(), true), ['logo' => true, 'theme' => true]));
        } finally {
            // La base de test est partagée : les tests de l'API lisent l'identité des fixtures
            $saved = $this->entityManager()->find(SiteIdentity::class, SiteIdentity::ID);
            self::assertInstanceOf(SiteIdentity::class, $saved);
            $default = SiteIdentity::byDefault();
            $saved->update($default->getName(), $default->getTagline(), null, null, $default->getDefaultColorScheme(), null, null);
            $this->entityManager()->flush();
        }
    }

    public function testRefusesAPrimaryColorUnreadableOnTheLightBackground(): void
    {
        $client = $this->loggedIn('admin-contraste@example.fr');

        $client->request('GET', '/admin/site-identity');
        $client->submitForm('Enregistrer', ['site_identity[useLibraryColors]' => false, 'site_identity[primaryColor]' => '#ffff00', 'site_identity[secondaryColor]' => '#ffffff']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#main form .alert-danger', 'La couleur principale doit contraster à 4,5:1 au moins avec le fond clair (actuellement 1,07:1).');
        self::assertNull($this->entityManager()->find(SiteIdentity::class, SiteIdentity::ID)?->getColors());
    }

    public function testRefusesASingleColor(): void
    {
        $client = $this->loggedIn('admin-couleur@example.fr');

        $client->request('GET', '/admin/site-identity');
        $client->submitForm('Enregistrer', ['site_identity[useLibraryColors]' => false, 'site_identity[primaryColor]' => '#c2410c']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#main form .alert-danger', 'Saisir les deux couleurs, ou aucune.');
    }

    public function testRefusesTheMessagesWithoutASession(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/contact-message');
        self::assertResponseRedirects('/admin/login');
    }

    public function testListsTheMessagesLatestFirstAndShowsOneInFull(): void
    {
        $client = $this->loggedIn('admin-messages@example.fr');
        $entityManager = $this->entityManager();
        $text = 'Est-il livré en pot ? <b class="piege">Merci</b> '.str_repeat('Une précision de plus. ', 5).'Fin du message.';
        $contact = new ContactMessage('Alex Contact', 'alex@example.fr', 'Bonjour, un simple message.', null, new \DateTimeImmutable('2100-01-01 12:00:00 UTC'));
        $inquiry = new ContactMessage('Camille Demande', 'camille@example.fr', $text, 'monstera-deliciosa', new \DateTimeImmutable('2100-01-02 08:30:00 UTC'));
        $entityManager->persist($contact);
        $entityManager->persist($inquiry);
        $entityManager->flush();

        try {
            $crawler = $client->request('GET', '/admin/contact-message');
            self::assertResponseIsSuccessful();
            $rows = $crawler->filter('table tbody tr');
            self::assertStringContainsString('Camille Demande', $rows->eq(0)->text());
            self::assertStringContainsString('02/01/2100 09:30', $rows->eq(0)->text());
            self::assertStringContainsString('monstera-deliciosa', $rows->eq(0)->text());
            self::assertStringContainsString('Est-il livré en pot ?', $rows->eq(0)->text());
            self::assertStringNotContainsString('Fin du message.', $rows->eq(0)->text());
            self::assertStringContainsString('Alex Contact', $rows->eq(1)->text());
            self::assertStringContainsString('alex@example.fr', $rows->eq(1)->text());
            self::assertStringContainsString('Aucun', $rows->eq(1)->text());
            self::assertStringNotContainsString('Aucun(e)', $rows->eq(1)->text());
            self::assertSelectorNotExists('a.action-new');
            self::assertSelectorNotExists('a.action-edit');
            self::assertSelectorNotExists('a.action-delete');

            $client->click($rows->eq(0)->filter('a.action-detail')->link());
            self::assertResponseIsSuccessful();
            self::assertSelectorTextContains('h1', 'Message de Camille Demande');
            self::assertSelectorTextContains('body', 'Fin du message.');
            self::assertSelectorTextContains('body', '<b class="piege">Merci</b>');
            self::assertSelectorNotExists('b.piege');
        } finally {
            // La base de test est partagée
            $entityManager = $this->entityManager();
            foreach ([$contact->getId(), $inquiry->getId()] as $id) {
                $saved = $entityManager->find(ContactMessage::class, $id);
                self::assertInstanceOf(ContactMessage::class, $saved);
                $entityManager->remove($saved);
            }
            $entityManager->flush();
        }
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

    public function testShowsTheCurrentLogoWithItsAltAboveTheUploadField(): void
    {
        $client = $this->loggedIn('admin-logo@example.fr');
        $identity = $this->entityManager()->find(SiteIdentity::class, SiteIdentity::ID);
        self::assertInstanceOf(SiteIdentity::class, $identity);
        $identity->update('Oraki', '', new Image(str_repeat('a', 32), 'Oraki, plantes rares', 120), null, DefaultColorScheme::Auto, null, new Image(str_repeat('b', 32), ''));
        $this->entityManager()->flush();

        try {
            $crawler = $client->request('GET', '/admin/site-identity');
            self::assertSelectorExists(\sprintf('[data-image-preview="site_identity_imageContent"]:not([hidden]) img[src="/media/%s-200.webp"]', str_repeat('a', 32)));
            self::assertSelectorTextContains('[data-image-preview="site_identity_imageContent"] figcaption', 'Logo actuel, texte de remplacement : « Oraki, plantes rares »');
            self::assertSelectorExists(\sprintf('[data-image-preview="site_identity_iconContent"]:not([hidden]) img[src="/media/%s-192.png"]', str_repeat('b', 32)));
            self::assertSelectorExists('[data-image-preview="site_identity_shareImageContent"][hidden]');
            // L'aperçu précède le champ d'envoi
            self::assertSame(1, $crawler->filter('[data-image-preview="site_identity_imageContent"] + div #site_identity_imageContent')->count());
        } finally {
            $this->resetSiteIdentity();
        }
    }

    public function testColorsAreColorInputsBehindTheLibraryColorsBox(): void
    {
        $client = $this->loggedIn('admin-couleurs@example.fr');

        $client->request('GET', '/admin/site-identity');

        self::assertSelectorExists('input[type="color"][name="site_identity[primaryColor]"]');
        self::assertSelectorExists('input[type="color"][name="site_identity[secondaryColor]"]');
        self::assertSelectorExists('input[type="checkbox"][name="site_identity[useLibraryColors]"][checked]');
    }

    public function testTurnsSalesOffAndChangesTheContactRecipient(): void
    {
        $client = $this->loggedIn('admin-vente@example.fr');

        try {
            $client->request('GET', '/admin/site-identity');
            self::assertSelectorTextContains('label[for="site_identity_salesEnabled"]', 'Vente en ligne (prix, panier et commande)');
            self::assertSelectorExists('input[type="checkbox"][name="site_identity[salesEnabled]"][checked]');
            self::assertSelectorExists('input[type="email"][name="site_identity[contactRecipient]"][value="contact@example.com"]');
            $client->submitForm('Enregistrer', ['site_identity[salesEnabled]' => false, 'site_identity[contactRecipient]' => 'ventes@oraki.fr']);
            self::assertResponseRedirects('/admin/site-identity');

            $client->request('GET', '/api/site-identity', [], [], ['HTTP_ACCEPT' => 'application/json']);
            /** @var array<string, mixed> $identity */
            $identity = json_decode((string) $client->getResponse()->getContent(), true);
            self::assertFalse($identity['salesEnabled']);
            self::assertArrayNotHasKey('contactRecipient', $identity);
            self::assertSame('ventes@oraki.fr', $this->entityManager()->find(SiteIdentity::class, SiteIdentity::ID)?->getContactRecipient());
        } finally {
            $this->resetSiteIdentity();
        }
    }

    public function testRequiresAValidContactRecipient(): void
    {
        $client = $this->loggedIn('admin-destinataire@example.fr');

        $client->request('GET', '/admin/site-identity');
        $client->submitForm('Enregistrer', ['site_identity[contactRecipient]' => 'pas-une-adresse']);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(SiteIdentity::DEFAULT_CONTACT_RECIPIENT, $this->entityManager()->find(SiteIdentity::class, SiteIdentity::ID)?->getContactRecipient());
    }

    public function testSavesAndRemovesTheIconAndTheShareImage(): void
    {
        $client = $this->loggedIn('admin-favicons@example.fr');
        $icon = $this->png(600, 512, 'icone.png');
        $share = $this->png(1200, 630, 'partage.png');

        try {
            $client->request('GET', '/admin/site-identity');
            $client->submitForm('Enregistrer', ['site_identity[iconContent]' => $icon, 'site_identity[shareImageContent]' => $share, 'site_identity[shareImageAlt]' => 'Serre tropicale']);
            self::assertResponseRedirects('/admin/site-identity');

            $client->request('GET', '/api/site-identity', [], [], ['HTTP_ACCEPT' => 'application/json']);
            /** @var array{icon: array<string, string>, shareImage: array{alt: string, width: int, height: int, src: string}} $identity */
            $identity = json_decode((string) $client->getResponse()->getContent(), true);
            self::assertSame(['png32', 'png180', 'png192', 'png512'], array_keys($identity['icon']));
            self::assertMatchesRegularExpression('#^http://localhost:8090/media/[0-9a-f]{32}-180\.png$#', $identity['icon']['png180']);
            self::assertFileExists(\dirname(__DIR__, 2).'/var/test-media/'.basename($identity['icon']['png512']));
            self::assertSame(['Serre tropicale', 1200, 630], [$identity['shareImage']['alt'], $identity['shareImage']['width'], $identity['shareImage']['height']]);

            $client->request('GET', '/admin/site-identity');
            $client->submitForm('Enregistrer', ['site_identity[removeIcon]' => true, 'site_identity[removeShareImage]' => true]);
            $client->request('GET', '/api/site-identity', [], [], ['HTTP_ACCEPT' => 'application/json']);
            self::assertSame(['shareImage' => null, 'icon' => null], array_intersect_key((array) json_decode((string) $client->getResponse()->getContent(), true), ['shareImage' => true, 'icon' => true]));
        } finally {
            unlink($icon);
            unlink($share);
            $this->resetSiteIdentity();
        }
    }

    public function testRefusesAnIconTooSmall(): void
    {
        $client = $this->loggedIn('admin-icone@example.fr');
        $icon = $this->png(256, 256, 'petite-icone.png');

        $client->request('GET', '/admin/site-identity');
        $client->submitForm('Enregistrer', ['site_identity[iconContent]' => $icon]);
        unlink($icon);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#main form .alert-danger', 'Recadrée au format 1:1, l\'image doit faire au moins 512 × 512 px : elle fait 256 × 256 px.');
    }

    public function testManagesTheGalleryOfAProduct(): void
    {
        $client = $this->loggedIn('admin-galerie@example.fr');
        $product = $this->entityManager()->getRepository(Product::class)->findOneBy(['slug' => 'palmier-nain']);
        self::assertInstanceOf(Product::class, $product);
        $narrow = $this->png(600, 600, 'galerie-etroite.png');

        try {
            $client->request('GET', \sprintf('/admin/product/%d/edit', $product->getId()));
            self::assertSelectorCount(2, 'input[type="file"][name^="product[gallery]"]');
            $client->submitForm('Enregistrer', [
                'product[gallery][0][content]' => $narrow,
                'product[gallery][0][position]' => '2',
                'product[gallery][1][content]' => \dirname(__DIR__, 2).'/fixtures/images/product-5.webp',
                'product[gallery][1][alt]' => 'Feuilles en éventail',
                'product[gallery][1][position]' => '1',
                'product[metaTitle]' => 'Palmier nain en pot',
                'product[metaDescription]' => 'Rustique et compact.',
            ]);
            self::assertResponseRedirects();

            $client->request('GET', '/api/products/palmier-nain', [], [], ['HTTP_ACCEPT' => 'application/json']);
            /** @var array{gallery: list<array{alt: string, width: int}>, metaTitle: string, metaDescription: string} $detail */
            $detail = json_decode((string) $client->getResponse()->getContent(), true);
            self::assertSame([['Feuilles en éventail', 800], ['Palmier nain (Chamaerops humilis)', 600]], array_map(static fn (array $image): array => [$image['alt'], $image['width']], $detail['gallery']));
            self::assertSame(['Palmier nain en pot', 'Rustique et compact.'], [$detail['metaTitle'], $detail['metaDescription']]);

            $client->request('GET', \sprintf('/admin/product/%d/edit', $product->getId()));
            self::assertSelectorCount(2, '[data-gallery-preview]');
            self::assertSelectorTextContains('[data-gallery-preview] figcaption', 'texte de remplacement : « Feuilles en éventail »');
            $client->submitForm('Enregistrer', ['product[gallery][0][remove]' => true]);
            self::assertResponseRedirects();
            $this->entityManager()->clear();
            $saved = $this->entityManager()->find(Product::class, $product->getId());
            self::assertInstanceOf(Product::class, $saved);
            self::assertSame(['Palmier nain (Chamaerops humilis)'], array_map(static fn (Image $image): string => $image->alt, $saved->getGallery()));
        } finally {
            unlink($narrow);
            // La base de test est partagée : les tests de l'API lisent le produit des fixtures, sans galerie ni métadonnées
            $saved = $this->entityManager()->getRepository(Product::class)->findOneBy(['slug' => 'palmier-nain']);
            self::assertInstanceOf(Product::class, $saved);
            $saved->changeGallery([]);
            $saved->changeMeta('', '');
            $this->entityManager()->flush();
        }
    }

    public function testRefusesAGalleryOfMoreThanEightImages(): void
    {
        $client = $this->loggedIn('admin-galerie-pleine@example.fr');
        $product = $this->entityManager()->getRepository(Product::class)->findOneBy(['slug' => 'palmier-nain']);
        self::assertInstanceOf(Product::class, $product);
        $image = $product->getImage();
        self::assertNotNull($image);
        $product->changeGallery(array_fill(0, 8, $image));
        $this->entityManager()->flush();

        try {
            $client->request('GET', \sprintf('/admin/product/%d/edit', $product->getId()));
            $client->submitForm('Enregistrer', ['product[gallery][8][content]' => \dirname(__DIR__, 2).'/fixtures/images/product-5.webp']);

            self::assertResponseStatusCodeSame(422);
            self::assertSelectorTextContains('#main form .alert-danger', 'La galerie compte 8 images au plus (9 demandées).');
        } finally {
            $saved = $this->entityManager()->getRepository(Product::class)->findOneBy(['slug' => 'palmier-nain']);
            self::assertInstanceOf(Product::class, $saved);
            $saved->changeGallery([]);
            $this->entityManager()->flush();
        }
    }

    public function testManagesSectionsAndRefusesToDeleteAUsedOne(): void
    {
        $client = $this->loggedIn('admin-rubriques@example.fr');

        $client->request('GET', '/admin/article-section/new');
        $client->submitForm('Enregistrer', ['article_term[name]' => 'Jardin', 'article_term[slug]' => 'jardin', 'article_term[position]' => '3']);
        self::assertResponseRedirects();
        $client->request('GET', '/api/article-sections', [], [], ['HTTP_ACCEPT' => 'application/json']);
        self::assertSame(['conseils', 'actualites', 'jardin'], array_column((array) json_decode((string) $client->getResponse()->getContent(), true), 'slug'));

        $crawler = $client->request('GET', '/admin/article-section');
        self::assertStringNotContainsString('Guide', $crawler->filter('#main table')->text());
        $token = (string) $crawler->filter('input[name="token"]')->attr('value');
        $used = $this->entityManager()->getRepository(ArticleTerm::class)->findOneBy(['kind' => ArticleTermKind::Section, 'slug' => 'conseils']);
        $unused = $this->entityManager()->getRepository(ArticleTerm::class)->findOneBy(['kind' => ArticleTermKind::Section, 'slug' => 'jardin']);
        self::assertInstanceOf(ArticleTerm::class, $used);
        self::assertInstanceOf(ArticleTerm::class, $unused);

        $client->request('POST', \sprintf('/admin/article-section/%d/delete', $used->getId()), ['token' => $token]);
        $client->followRedirect();
        self::assertSelectorTextContains('.alert-danger', 'La rubrique Conseils est utilisée par 2 articles.');

        $client->request('POST', \sprintf('/admin/article-section/%d/delete', $unused->getId()), ['token' => $token]);
        $this->entityManager()->clear();
        self::assertNull($this->entityManager()->getRepository(ArticleTerm::class)->findOneBy(['slug' => 'jardin']));
    }

    public function testRefusesATakenFormatSlug(): void
    {
        $client = $this->loggedIn('admin-formats@example.fr');

        $client->request('GET', '/admin/article-format/new');
        $client->submitForm('Enregistrer', ['article_term[name]' => 'Guide bis', 'article_term[slug]' => 'guide', 'article_term[position]' => '2']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#main form .alert-danger', 'Le slug « guide » est déjà pris par un autre format.');
    }

    public function testClassifiesAnArticleAndSendsThenRemovesItsAudio(): void
    {
        $client = $this->loggedIn('admin-audio@example.fr');
        $article = $this->entityManager()->getRepository(Article::class)->findOneBy(['slug' => 'le-mode-accessibilite-renforcee']);
        self::assertInstanceOf(Article::class, $article);
        $guide = $this->entityManager()->getRepository(ArticleTerm::class)->findOneBy(['kind' => ArticleTermKind::Format, 'slug' => 'guide']);
        self::assertInstanceOf(ArticleTerm::class, $guide);

        try {
            $client->request('GET', \sprintf('/admin/article/%d/edit', $article->getId()));
            self::assertSelectorNotExists('[data-audio-preview]');
            $client->submitForm('Enregistrer', [
                'article[formatId]' => (string) $guide->getId(),
                'article[audioPath]' => \dirname(__DIR__).'/Fixtures/audio/beep-3s.m4a',
                'article[speaker]' => 'Présenté par Camille Exemple',
                'article[metaDescription]' => 'Texte agrandi et contraste AAA.',
            ]);
            self::assertResponseRedirects();

            $client->request('GET', '/api/articles/le-mode-accessibilite-renforcee', [], [], ['HTTP_ACCEPT' => 'application/json']);
            /** @var array{format: array{slug: string}, audio: array{src: string, type: string, durationSeconds: int}, speaker: string, metaDescription: string} $detail */
            $detail = json_decode((string) $client->getResponse()->getContent(), true);
            self::assertSame(['guide', 'audio/mp4', 3, 'Présenté par Camille Exemple', 'Texte agrandi et contraste AAA.'], [$detail['format']['slug'], $detail['audio']['type'], $detail['audio']['durationSeconds'], $detail['speaker'], $detail['metaDescription']]);
            self::assertMatchesRegularExpression('#^http://localhost:8090/media/[0-9a-f]{32}\.m4a$#', $detail['audio']['src']);
            self::assertFileExists(\dirname(__DIR__, 2).'/var/test-media/'.basename($detail['audio']['src']));

            $client->request('GET', '/api/articles?format=guide', [], [], ['HTTP_ACCEPT' => 'application/json']);
            self::assertSame([3, 1], array_column((array) json_decode((string) $client->getResponse()->getContent(), true), 'id'));

            $client->request('GET', \sprintf('/admin/article/%d/edit', $article->getId()));
            self::assertSelectorTextContains('[data-audio-preview] figcaption', 'Fichier audio actuel, M4A, 3 s');
            $client->submitForm('Enregistrer', ['article[removeAudio]' => true]);
            $client->request('GET', '/api/articles/le-mode-accessibilite-renforcee', [], [], ['HTTP_ACCEPT' => 'application/json']);
            self::assertNull(((array) json_decode((string) $client->getResponse()->getContent(), true))['audio']);
        } finally {
            // La base de test est partagée : les tests de l'API lisent l'article des fixtures
            $saved = $this->entityManager()->getRepository(Article::class)->findOneBy(['slug' => 'le-mode-accessibilite-renforcee']);
            self::assertInstanceOf(Article::class, $saved);
            $saved->classify($saved->getSection(), null);
            $saved->changeAudio(null, '');
            $saved->changeMeta('', '');
            $this->entityManager()->flush();
        }
    }

    public function testRefusesAFileThatIsNotAudio(): void
    {
        $client = $this->loggedIn('admin-audio-refuse@example.fr');
        $article = $this->entityManager()->getRepository(Article::class)->findOneBy(['slug' => 'le-mode-accessibilite-renforcee']);
        self::assertInstanceOf(Article::class, $article);

        $client->request('GET', \sprintf('/admin/article/%d/edit', $article->getId()));
        $client->submitForm('Enregistrer', ['article[audioPath]' => \dirname(__DIR__).'/Fixtures/audio/video-1s.mp4']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#main form .alert-danger', 'Le fichier ne se lit pas comme un fichier audio MP3, M4A ou OGG.');
    }

    public function testEditsTheMetadataOfAFixedPage(): void
    {
        $client = $this->loggedIn('admin-referencement@example.fr');
        $home = $this->entityManager()->getRepository(PageMeta::class)->findOneBy(['page' => MetaPage::Home]);
        self::assertInstanceOf(PageMeta::class, $home);

        try {
            $client->request('GET', '/admin/page-meta');
            self::assertSelectorTextContains('#main table', 'Données personnelles');
            self::assertSelectorNotExists('a.action-new');
            $client->request('GET', \sprintf('/admin/page-meta/%d/edit', $home->getId()));
            $client->submitForm('Enregistrer', ['page_meta[title]' => 'Oraki, plantes rares', 'page_meta[description]' => 'Plantes tropicales livrées chez vous.']);
            self::assertResponseRedirects();

            $client->request('GET', '/api/page-metas', [], [], ['HTTP_ACCEPT' => 'application/json']);
            /** @var list<array{page: string, title: string, description: string}> $metas */
            $metas = json_decode((string) $client->getResponse()->getContent(), true);
            self::assertSame(['page' => 'home', 'title' => 'Oraki, plantes rares', 'description' => 'Plantes tropicales livrées chez vous.'], $metas[0]);

            $client->request('GET', \sprintf('/admin/page-meta/%d/edit', $home->getId()));
            $client->submitForm('Enregistrer', ['page_meta[title]' => str_repeat('a', 61)]);
            self::assertResponseStatusCodeSame(422);
        } finally {
            $saved = $this->entityManager()->getRepository(PageMeta::class)->findOneBy(['page' => MetaPage::Home]);
            self::assertInstanceOf(PageMeta::class, $saved);
            $saved->update('', '');
            $this->entityManager()->flush();
        }
    }

    /**
     * @param positive-int $width
     * @param positive-int $height
     */
    private function png(int $width, int $height, string $name): string
    {
        $image = imagecreatetruecolor($width, $height);
        self::assertNotFalse($image);
        $path = sys_get_temp_dir().'/'.$name;
        imagepng($image, $path);

        return $path;
    }

    private function resetSiteIdentity(): void
    {
        // La base de test est partagée : les tests de l'API lisent l'identité des fixtures
        $saved = $this->entityManager()->find(SiteIdentity::class, SiteIdentity::ID);
        self::assertInstanceOf(SiteIdentity::class, $saved);
        $default = SiteIdentity::byDefault();
        $saved->update($default->getName(), $default->getTagline(), null, null, $default->getDefaultColorScheme(), null, null);
        $saved->changeSales($default->isSalesEnabled(), $default->getContactRecipient());
        $this->entityManager()->flush();
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
