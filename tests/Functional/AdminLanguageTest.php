<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Admin\Infrastructure\Security\AdminUser;
use App\Content\Domain\LegalPage;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Interface de l'admin en français et en anglais (SPEC-COMMUNE, section 29, ADR 0015).
 */
final class AdminLanguageTest extends WebTestCase
{
    private const string PASSWORD = 'un-mot-de-passe-de-test';
    private const array SECTIONS = ['category', 'product', 'product-attribute', 'article', 'article-section', 'article-format', 'legal-page', 'page-meta', 'contact-message'];
    private const array WITHOUT_NEW = ['legal-page', 'page-meta', 'contact-message'];

    protected function setUp(): void
    {
        $rateLimiterCache = static::getContainer()->get('cache.rate_limiter');
        self::assertInstanceOf(\Psr\Cache\CacheItemPoolInterface::class, $rateLimiterCache);
        $rateLimiterCache->clear();
        self::ensureKernelShutdown();
    }

    public function testTheInterfaceIsInFrenchByDefault(): void
    {
        $client = $this->loggedIn('admin-langue-fr@example.fr');
        $crawler = $client->request('GET', '/admin/product');

        self::assertSame('fr', $crawler->filter('html')->attr('lang'));
        self::assertSelectorTextContains('#main-menu', 'Produits');
        $this->assertNoLabelOf('en', $client, 'fr');
    }

    public function testTheChosenLanguageIsKeptForTheSession(): void
    {
        $client = $this->loggedIn('admin-langue-choix@example.fr');
        $crawler = $client->request('GET', '/admin/product');
        $english = $crawler->filter('a[href*="_locale=en"]');
        self::assertGreaterThan(0, $english->count(), 'sélecteur de langue');
        $client->click($english->first()->link());
        self::assertSelectorTextContains('#main-menu', 'Products');

        $crawler = $client->request('GET', '/admin/category');
        self::assertSame('en', $crawler->filter('html')->attr('lang'));
        self::assertSelectorTextContains('#main-menu', 'Categories');

        $client->request('GET', '/admin/category?_locale=fr');
        self::assertSelectorTextContains('#main-menu', 'Catégories');
    }

    public function testTheWholeInterfaceIsInEnglish(): void
    {
        $client = $this->loggedIn('admin-langue-en@example.fr');
        // Site bilingue : les onglets de langue et leurs textes sont vérifiés aussi
        $client->setServerParameter('HTTP_X_SCENARIO', 'bilingual');
        $client->request('GET', '/admin/product?_locale=en');

        $this->assertNoLabelOf('fr', $client, 'en');
    }

    public function testRuleViolationsAndValidationMessagesAreTranslated(): void
    {
        $client = $this->loggedIn('admin-langue-regles@example.fr');
        $client->request('GET', '/admin/product-attribute/new?_locale=en');
        $client->submitForm('Save', ['product_attribute[code]' => 'feuillage', 'product_attribute[label]' => 'Doublon', 'product_attribute[position]' => '9']);
        self::assertSelectorTextContains('#main form .alert-danger', 'The code “feuillage” is already used by another attribute.');

        $client->request('GET', '/admin/product-attribute/new');
        $client->submitForm('Save', ['product_attribute[code]' => 'Pas un code', 'product_attribute[label]' => 'Libellé', 'product_attribute[position]' => '-1']);
        self::assertSelectorTextContains('#main form', 'Lowercase letters, digits and hyphens, 40 characters at most, starting with a letter.');
        self::assertSelectorTextContains('#main form', 'This value should be either positive or zero.');
    }

    public function testAMonolingualSiteHasNoLanguageTab(): void
    {
        $client = $this->loggedIn('admin-onglets-aucun@example.fr');
        foreach (['product', 'category', 'article', 'legal-page'] as $section) {
            $crawler = $client->request('GET', '/admin/'.$section);
            $client->click($crawler->filter('a.action-edit')->first()->link());
            self::assertSelectorNotExists('[data-language-tabs]', $section);
            self::assertSelectorNotExists('[name*="[translations]"]', $section);
        }
        $client->request('GET', '/admin/site-identity');
        self::assertSelectorNotExists('[data-language-tabs]');
    }

    public function testATranslatableRecordHasOneTabPerLanguage(): void
    {
        $client = $this->loggedIn('admin-onglets@example.fr');
        $client->setServerParameter('HTTP_X_SCENARIO', 'bilingual');
        $crawler = $client->request('GET', $this->editUrl($client, 'product', 'Monstera deliciosa'));

        self::assertSame(['Français', 'English'], $crawler->filter('[data-language-tabs] [role="tab"]')->each(static fn (Crawler $tab): string => trim($tab->text())));
        self::assertSame('Monstera deliciosa', $crawler->filter('#language-panel-fr [name="product[name]"]')->attr('value'));
        self::assertSame('Swiss cheese plant', $crawler->filter('#language-panel-en [name="product[translations][en][name]"]')->attr('value'));
        self::assertSame('required', $crawler->filter('[name="product[name]"]')->attr('required'));
        self::assertNull($crawler->filter('[name="product[translations][en][name]"]')->attr('required'));
        self::assertCount(1, $crawler->filter('[name^="product[gallery][0][alts][en]"]'), 'texte de remplacement anglais de la galerie');
    }

    public function testSavesTheTranslationsAndRefusesASlugTakenInTheLanguage(): void
    {
        [$client, $connection] = $this->isolated('admin-onglets-ecriture@example.fr');
        $client->setServerParameter('HTTP_X_SCENARIO', 'bilingual');
        try {
            $client->request('GET', $this->editUrl($client, 'product', 'Monstera adansonii'));
            $client->submitForm('Enregistrer', ['product[translations][en][slug]' => 'swiss-cheese-plant']);
            self::assertResponseStatusCodeSame(422);
            self::assertSelectorTextContains('#main form .alert-danger', 'Le slug « swiss-cheese-plant » est déjà pris par un autre produit.');

            $client->submitForm('Enregistrer', ['product[translations][en][name]' => 'Five holes plant', 'product[translations][en][slug]' => 'five-holes-plant']);
            self::assertResponseRedirects();
            $client->request('GET', '/api/products/five-holes-plant?locale=en', [], [], ['HTTP_ACCEPT' => 'application/json']);
            self::assertResponseIsSuccessful();
            self::assertStringContainsString('"name":"Five holes plant"', (string) $client->getResponse()->getContent());
        } finally {
            $connection->rollBack();
        }
    }

    public function testEnablingALanguageInTheIdentityAddsTheTabs(): void
    {
        [$client, $connection] = $this->isolated('admin-langues-site@example.fr');
        try {
            $this->submitIdentity($client, 'fr', ['en']);
            self::assertResponseRedirects();
            $crawler = $client->followRedirect();
            self::assertSelectorExists('[data-language-tabs]');
            self::assertSame('Website template with boilerplates-ui, equipped for QA and end-to-end testing.', $crawler->filter('[name="site_identity[translations][en][tagline]"]')->attr('value'));

            $client->request('GET', $this->editUrl($client, 'category', 'Feuillages'));
            self::assertSelectorExists('#language-panel-en [name="category[translations][en][name]"]');
            $client->request('GET', '/api/site-identity', [], [], ['HTTP_ACCEPT' => 'application/json']);
            self::assertStringContainsString('"locales":["fr","en"]', (string) $client->getResponse()->getContent());
        } finally {
            $connection->rollBack();
        }
    }

    public function testAnotherDefaultLanguageMovesTheTexts(): void
    {
        [$client, $connection] = $this->isolated('admin-langue-defaut@example.fr');
        try {
            $this->submitIdentity($client, 'en', ['fr']);
            self::assertResponseRedirects();

            $client->request('GET', '/api/products/swiss-cheese-plant', [], [], ['HTTP_ACCEPT' => 'application/json', 'HTTP_ACCEPT_LANGUAGE' => '']);
            self::assertResponseIsSuccessful();
            self::assertResponseHeaderSame('Content-Language', 'en');
            $client->request('GET', '/api/products/monstera-deliciosa?locale=fr', [], [], ['HTTP_ACCEPT' => 'application/json']);
            self::assertResponseIsSuccessful();
            self::assertStringContainsString('"name":"Monstera deliciosa"', (string) $client->getResponse()->getContent());
            $crawler = $client->request('GET', $this->editUrl($client, 'product', 'Swiss cheese plant'));
            self::assertSame(['English', 'Français'], $crawler->filter('[data-language-tabs] [role="tab"]')->each(static fn (Crawler $tab): string => trim($tab->text())));
        } finally {
            $connection->rollBack();
        }
    }

    public function testTheLoginPageFollowsTheChosenLanguage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/login?_locale=en');
        self::assertSelectorExists('button:contains("Sign in")');

        $client->submitForm('Sign in', ['_username' => 'inconnu@example.fr', '_password' => 'faux']);
        $client->followRedirect();
        self::assertSelectorTextContains('.alert-danger', 'Invalid credentials.');
    }

    /**
     * Aucun libellé propre à l'autre langue sur les pages de l'admin : liste, création, modification et fiche de chaque
     * section, identité du site. Les données (cellules, valeurs, textes saisis) sont écartées : elles ne sont pas des
     * libellés.
     */
    private function assertNoLabelOf(string $otherLocale, KernelBrowser $client, string $locale): void
    {
        $labels = $this->labelsOnlyIn($otherLocale, $locale);
        $pages = ['/admin/site-identity'];
        foreach (self::SECTIONS as $section) {
            $crawler = $client->request('GET', '/admin/'.$section);
            self::assertResponseIsSuccessful($section);
            $pages[] = '/admin/'.$section;
            $detail = $crawler->filter('a.action-detail');
            if ($detail->count() > 0) {
                $pages[] = (string) $detail->first()->attr('href');
            }
            $edit = $crawler->filter('a.action-edit');
            if ($edit->count() > 0) {
                $pages[] = (string) $edit->first()->attr('href');
            }
            if (!\in_array($section, self::WITHOUT_NEW, true)) {
                $pages[] = '/admin/'.$section.'/new';
            }
        }

        foreach ($pages as $page) {
            $crawler = $client->request('GET', $page);
            self::assertResponseIsSuccessful($page);
            self::assertSame($locale, $crawler->filter('html')->attr('lang'), $page);
            $text = $this->interfaceText($crawler);
            foreach ($labels as $label) {
                self::assertDoesNotMatchRegularExpression('/(?<![\p{L}\p{N}])'.preg_quote($label, '/').'(?![\p{L}\p{N}])/u', $text, \sprintf('« %s » sur %s', $label, $page));
            }
        }
    }

    /**
     * Morceaux des textes de la langue $otherLocale (paramètres retirés) absents des textes de $locale : « Article »,
     * qui s'écrit pareil dans les deux langues, n'en est pas un.
     *
     * @return list<string>
     */
    private function labelsOnlyIn(string $otherLocale, string $locale): array
    {
        $labels = [];
        $mineText = '';
        foreach (['admin', 'validators'] as $domain) {
            $other = Yaml::parseFile(\sprintf('%s/translations/%s.%s.yaml', \dirname(__DIR__, 2), $domain, $otherLocale));
            $mine = Yaml::parseFile(\sprintf('%s/translations/%s.%s.yaml', \dirname(__DIR__, 2), $domain, $locale));
            self::assertIsArray($other);
            self::assertIsArray($mine);
            $mineText .= ' '.implode(' ', array_filter($mine, \is_string(...)));
            foreach ($other as $key => $value) {
                self::assertIsString($value);
                if ($value === ($mine[$key] ?? null)) {
                    continue;
                }
                foreach (preg_split('/%\w+%/', $value) ?: [] as $part) {
                    $part = trim($part, " \u{a0},.:;«»“”()");
                    if (mb_strlen($part) >= 3) {
                        $labels[] = $part;
                    }
                }
            }
        }

        return array_values(array_filter(
            array_unique($labels),
            static fn (string $label): bool => 1 !== preg_match('/(?<![\p{L}\p{N}])'.preg_quote($label, '/').'(?![\p{L}\p{N}])/u', $mineText),
        ));
    }

    /**
     * Texte de la page sans les données : cellules des listes, valeurs des fiches, textes saisis, titres des pages
     * légales (« Mentions légales » est aussi un libellé de page fixe, et le titre des pages de modification).
     */
    private function interfaceText(Crawler $crawler): string
    {
        $document = $crawler->getNode(0)?->ownerDocument;
        self::assertNotNull($document);
        $body = new Crawler($document->saveHTML() ?: '');
        $body->filter('td, .field-value, textarea')->each(static function (Crawler $node): void {
            $element = $node->getNode(0);
            if (null !== $element) {
                $element->parentNode?->removeChild($element);
            }
        });
        $text = $body->filter('body')->text();
        foreach ($this->entityManager()->getRepository(LegalPage::class)->findAll() as $page) {
            $text = str_replace($page->getTitle(), '', $text);
        }

        return $text;
    }

    /**
     * Client sans redémarrage du noyau, dans une transaction que le test annule : la base de test est partagée.
     *
     * @return array{KernelBrowser, Connection}
     */
    private function isolated(string $email): array
    {
        $client = $this->loggedIn($email);
        $client->disableReboot();
        $client->request('GET', '/admin/product');
        $entityManager = $client->getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $connection = $entityManager->getConnection();
        $connection->beginTransaction();

        return [$client, $connection];
    }

    /**
     * @param list<string> $otherLocales
     */
    private function submitIdentity(KernelBrowser $client, string $defaultLocale, array $otherLocales): void
    {
        $form = $client->request('GET', '/admin/site-identity')->selectButton('Enregistrer')->form(['site_identity[defaultLocale]' => $defaultLocale]);
        $boxes = $form->get('site_identity[otherLocales]');
        self::assertIsArray($boxes);
        foreach ($boxes as $box) {
            self::assertInstanceOf(ChoiceFormField::class, $box);
            \in_array($box->availableOptionValues()[0] ?? null, $otherLocales, true) ? $box->tick() : $box->untick();
        }
        $client->submit($form);
    }

    private function editUrl(KernelBrowser $client, string $section, string $name): string
    {
        $crawler = $client->request('GET', '/admin/'.$section.'?query='.rawurlencode($name));
        $edit = $crawler->filter('a.action-edit');
        self::assertGreaterThan(0, $edit->count(), $name);

        return (string) $edit->first()->attr('href');
    }

    private function loggedIn(string $email): KernelBrowser
    {
        $client = static::createClient();
        $entityManager = $this->entityManager();
        if (null === $entityManager->getRepository(AdminUser::class)->findOneBy(['email' => $email])) {
            $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
            self::assertInstanceOf(UserPasswordHasherInterface::class, $hasher);
            $user = new AdminUser($email, '');
            $user->changePassword($hasher->hashPassword($user, self::PASSWORD));
            $entityManager->persist($user);
            $entityManager->flush();
        }
        $client->request('GET', '/admin/login');
        $client->submitForm('Se connecter', ['_username' => $email, '_password' => self::PASSWORD]);
        self::assertResponseRedirects('/admin');

        return $client;
    }

    private function entityManager(): EntityManagerInterface
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        return $entityManager;
    }
}
