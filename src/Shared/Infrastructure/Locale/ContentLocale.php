<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Locale;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Langue du contenu servi par l'API pour la requête en cours (SPEC-COMMUNE, section 29, ADR 0015) : le paramètre
 * `locale` s'il désigne une langue activée, sinon la première langue activée d'Accept-Language, sinon la langue par
 * défaut. Indépendante de la langue de Symfony (messages de validation), qui ne change pas.
 */
final readonly class ContentLocale
{
    public const string PARAMETER = 'locale';
    private const string LOCALES_ATTRIBUTE = '_content_locales';
    private const string LOCALE_ATTRIBUTE = '_content_locale';

    public function __construct(
        private RequestStack $requests,
        private EnabledLocales $enabledLocales,
    ) {
    }

    /**
     * Langues activées, la langue par défaut en premier. Lues une fois par requête.
     *
     * @return non-empty-list<string>
     */
    public function locales(): array
    {
        $request = $this->requests->getMainRequest();
        if (null === $request) {
            return $this->enabledLocales->enabled();
        }
        /** @var non-empty-list<string>|null $locales posé par cette méthode seule */
        $locales = $request->attributes->get(self::LOCALES_ATTRIBUTE);
        if (null === $locales) {
            $locales = $this->enabledLocales->enabled();
            $request->attributes->set(self::LOCALES_ATTRIBUTE, $locales);
        }

        return $locales;
    }

    public function defaultLocale(): string
    {
        return $this->locales()[0];
    }

    /**
     * Langue demandée et activée, ou la langue par défaut.
     */
    public function current(): string
    {
        $request = $this->requests->getMainRequest();
        if (null === $request) {
            return $this->defaultLocale();
        }
        $locale = $request->attributes->get(self::LOCALE_ATTRIBUTE);
        if (!\is_string($locale)) {
            $locale = self::negotiate($this->locales(), $request->query->all()[self::PARAMETER] ?? null, $request->headers->get('Accept-Language'));
            $request->attributes->set(self::LOCALE_ATTRIBUTE, $locale);
        }

        return $locale;
    }

    public function isMultilingual(): bool
    {
        return \count($this->locales()) > 1;
    }

    /**
     * Versions de l'élément dans chaque langue activée, langue demandée comprise ; [] sur un site monolingue.
     *
     * @param callable(string): string $slugIn slug de l'élément dans une langue (le sien ou, à défaut, celui de la langue par défaut)
     *
     * @return list<Alternate>
     */
    public function alternates(callable $slugIn): array
    {
        if (!$this->isMultilingual()) {
            return [];
        }

        return array_map(static fn (string $locale): Alternate => new Alternate($locale, $slugIn($locale)), $this->locales());
    }

    /**
     * Le paramètre l'emporte s'il désigne une langue activée ; sinon, la première langue d'Accept-Language (par poids
     * décroissant, ordre d'écriture à poids égal, sous-étiquette de région ignorée, poids nul exclu) qui est activée ;
     * sinon la langue par défaut. Comparaisons sans casse.
     *
     * @param non-empty-list<string> $enabled
     */
    public static function negotiate(array $enabled, mixed $parameter, ?string $acceptLanguage): string
    {
        if (\is_string($parameter) && \in_array(self::language($parameter), $enabled, true)) {
            return self::language($parameter);
        }
        foreach (self::acceptedLanguages($acceptLanguage ?? '') as $language) {
            if (\in_array($language, $enabled, true)) {
                return $language;
            }
        }

        return $enabled[0];
    }

    /**
     * @return list<string> langues d'Accept-Language par poids décroissant, sans région
     */
    private static function acceptedLanguages(string $header): array
    {
        $entries = [];
        foreach (explode(',', $header) as $index => $part) {
            $pieces = array_map(trim(...), explode(';', $part));
            $tag = array_shift($pieces);
            $quality = 1.0;
            foreach ($pieces as $piece) {
                if (1 === preg_match('/^q=([01](?:\.\d{0,3})?)$/i', $piece, $match)) {
                    $quality = (float) $match[1];
                }
            }
            if ('' !== $tag && $quality > 0) {
                $entries[] = ['language' => self::language($tag), 'quality' => $quality, 'index' => $index];
            }
        }
        usort($entries, static fn (array $a, array $b): int => [$b['quality'], $a['index']] <=> [$a['quality'], $b['index']]);

        return array_column($entries, 'language');
    }

    /** « en-GB » → « en ». */
    private static function language(string $tag): string
    {
        return strtolower(explode('-', str_replace('_', '-', trim($tag)))[0]);
    }

    public static function isApiRequest(Request $request): bool
    {
        $path = $request->getPathInfo();

        return str_starts_with($path, '/api/') && !str_starts_with($path, '/api/docs');
    }
}
