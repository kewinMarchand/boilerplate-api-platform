<?php

declare(strict_types=1);

namespace App\Tests\Unit\Admin;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * Fichiers de traduction de l'admin (ADR 0015) : mêmes clés en français et en anglais, toute clé citée dans le code
 * définie, toute règle du domaine traduite en anglais.
 */
final class AdminTranslationsTest extends TestCase
{
    /** Préfixes des clés de l'admin, pour les retrouver dans le code. */
    private const string KEY = '/[\'"]((?:article|attribute_type|audio|category|color_scheme|constraint|dashboard|entity|exposure|field|flash|form|gallery|help|image|login|message|meta_page|product|tabs|title|upload)\.[a-z0-9_.-]+)[\'"]/';

    #[DataProvider('domains')]
    public function testFrenchAndEnglishHaveTheSameKeys(string $domain): void
    {
        self::assertSame(array_keys($this->catalogue($domain, 'fr')), array_keys($this->catalogue($domain, 'en')));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function domains(): iterable
    {
        yield 'admin' => ['admin'];
        yield 'validators' => ['validators'];
    }

    public function testEveryKeyCitedInTheCodeIsDefined(): void
    {
        $defined = [...array_keys($this->catalogue('admin', 'fr')), ...array_keys($this->catalogue('validators', 'fr'))];
        $finder = (new Finder())->files()->in([\dirname(__DIR__, 3).'/src/Admin', \dirname(__DIR__, 3).'/templates/admin']);
        $cited = [];
        foreach ($finder as $file) {
            preg_match_all(self::KEY, $file->getContents(), $matches);
            $cited = [...$cited, ...$matches[1]];
        }
        // Clés composées dans le code : préfixe suivi d'une valeur d'énumération
        $cited = array_filter(array_unique($cited), static fn (string $key): bool => !str_ends_with($key, '.'));

        self::assertNotEmpty($cited);
        self::assertSame([], array_values(array_diff($cited, $defined)));
    }

    public function testEveryRuleOfTheDomainIsTranslatedIntoEnglish(): void
    {
        $finder = (new Finder())->files()->in(\dirname(__DIR__, 3).'/src')->name('*.php');
        $templates = [];
        foreach ($finder as $file) {
            preg_match_all("/RuleViolation\\((?:1 === [^?]+\\? )?'((?:[^'\\\\]|\\\\.)*)'(?: : '((?:[^'\\\\]|\\\\.)*)')?/", $file->getContents(), $matches);
            $templates = [...$templates, ...$matches[1], ...array_filter($matches[2])];
            if ('ArticleTermKind.php' === $file->getFilename()) {
                preg_match_all("/(?:=> |\\? |: )'((?:[^'\\\\]|\\\\.)*[.])'/", $file->getContents(), $matches);
                $templates = [...$templates, ...$matches[1]];
            }
        }
        $templates = array_unique(array_map(static fn (string $template): string => str_replace("\\'", "'", $template), $templates));

        self::assertGreaterThan(50, \count($templates));
        self::assertSame([], array_values(array_diff($templates, array_keys($this->catalogue('violations', 'en')))));
    }

    /**
     * @return array<string, string>
     */
    private function catalogue(string $domain, string $locale): array
    {
        /** @var array<string, string> $catalogue fichier plat, une clé et un texte par ligne */
        $catalogue = Yaml::parseFile(\sprintf('%s/translations/%s.%s.yaml', \dirname(__DIR__, 3), $domain, $locale));

        return $catalogue;
    }
}
