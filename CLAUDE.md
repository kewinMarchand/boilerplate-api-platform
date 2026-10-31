# CLAUDE.md — boilerplate-api-platform

API de test commune aux boilerplates front. Les conventions générales sont dans `~/.claude/CLAUDE.md` : ce fichier ne note que les choix propres au projet.

## Choix du projet

- **Rôle** : servir des données stables aux fronts (tâches, articles, contact, catalogue). Pas d'authentification, pas d'écriture hors contact.
- **Administration à venir** ([ADR 0001](doc/adr/0001-administration-easyadmin-dans-l-api.md), acceptée dans `46df658`) : EasyAdmin sous `/admin`, derrière Symfony Security, pour le catalogue, les articles, le carrousel et les pages légales. Le contrat public reste en lecture (hors contact) : l'admin écrit par les cas d'usage de la couche application, jamais par des opérations API Platform. Les fixtures restent les données de développement et de test. Tant qu'elle n'est pas implémentée, la ligne « Rôle » ci-dessus décrit l'état réel.
- **Architecture** : hexagonale légère. L'API est **un** contexte borné (un déploiement, une base, un langage), découpé en **modules** par sous-domaine (`Task`, `Blog`, `Contact`, `Catalog`). Ne pas les appeler « contextes ». Référence : `~/Applications/boilerplates-commun/ARCHITECTURE-DDD.md`. `Domain/` = modèle + port, sans framework. `Infrastructure/Doctrine/` = mapping XML, adaptateur, fixtures. `Infrastructure/ApiPlatform/` = ressource exposée + provider ou processor. Couche `Application` dans `Catalog` seulement : `SearchProducts` charge produits et arbre par les deux ports et délègue à `ProductSearch`. Le provider ne fait plus que lire les paramètres et mapper. Blog, Contact et Task restent des transaction scripts sans `Application` : elle ne ferait que relayer le dépôt.
- **Ressources séparées du modèle** : les classes `#[ApiResource]` sont des DTO construits par `fromModel()`. La validation (messages français, identiques à `contactSchema.ts`) est portée par `ContactMessageResource`.
- **Format par défaut** : `json` déclaré avant `jsonld` dans `api_platform.yaml`. Une requête sans `Accept` reçoit un tableau JSON simple, ce que les fronts consomment sans Hydra.
- **Pagination** désactivée (`pagination_enabled: false`).
- **Propriétés de sortie `required: true`** : sans cela, l'OpenAPI les rend optionnelles et les types générés côté front portent des `?` partout.
- **`publishedAt`** : colonne `date_immutable`, exposée en `AAAA-MM-JJ` (`format: date` dans le schéma), comme les dépôts en mémoire des fronts.
- **Catalogue** : `GET /api/products` est une opération `Get` (uriTemplate `/products`) qui renvoie un objet `ProductList` (page, total, facettes) : une `GetCollection` sérialise un tableau en JSON simple et ne peut pas porter les compteurs. Les filtres sont des `QueryParameter` (documentés dans l'OpenAPI), lus par `ProductListProvider` puis passés à `Domain/ProductSearch`, qui filtre, compte, trie et pagine en PHP. Avec 24 produits, c'est plus lisible que des requêtes SQL par facette. À revoir si le catalogue devient gros.
- **Value objects du catalogue** : `Price` (centimes, jamais négatif, reconstruit à la lecture depuis la colonne entière, donc sans migration) et `CategoryTree` (recherche par slug, enfants, branches), partagé par `ProductSearch` et `CategoryTreeProvider`. Pas d'agrégat tant qu'il n'y a aucune écriture ; avec l'administration (ADR 0001), `Category` et `Product` deviennent des agrégats (slug unique dans sa branche, parent existant, 3 niveaux au plus, prix positif ou nul). Pas d'`Email` ni de `Slug` (aucune règle propre au-delà de la validation de frontière).
- **Paramètres invalides ignorés**, pas de 422 : c'est la règle des URL côté front, et les 422 générées par API Platform depuis le schéma des paramètres sont en anglais. D'où l'absence de `minimum`, `maximum` et `enum` sur les paramètres scalaires (voir pièges).
- **`category` inconnue** : `CategoryNotFound` (domaine) est mappée en 404 par `exception_to_status`.
- **X-Scenario** : `ScenarioProvider` décore `api_platform.state_provider.main` en priorité 400, entre la désérialisation (300) et la lecture (500). Enregistré par `#[When('dev')]` et `#[When('test')]`. Pour une opération non collection, `empty` demande à la ressource sa forme vide via l'interface `EmptyScenarioResult` (implémentée par `ProductListResource`).
- **CSP** : `Content-Security-Policy: default-src 'none'; frame-ancestors 'none'` sur toutes les réponses sauf le HTML (`Shared/Infrastructure/Http/ContentSecurityPolicySubscriber`), même en-tête que l'API Hono. Une API JSON ne charge rien et ne s'affiche pas en iframe ; la Swagger UI de `/api/docs` charge ses scripts et en est exclue. CSP bloquante et sans `report-uri` : il n'y a pas de page dont collecter les violations.
- **CORS** : regex dans `CORS_ALLOW_ORIGIN`, limitée aux chemins `^/api/`.
- **Prérequis** : Docker avec Compose, `make` et git, rien d'autre (section 18 de `SPEC-COMMUNE.md`). Toute commande passe par `make` et les conteneurs, en local comme en CI. `EXEC` reste surchargeable, sans usage aujourd'hui.
- **Docker** : FrankenPHP (un seul conteneur pour HTTP et PHP), image dev lancée avec l'UID de l'hôte (`HOST_UID`/`HOST_GID` exportés par le Makefile), donc écoute sur 8080 dans le conteneur. PostgreSQL non publié sur l'hôte.

## Versions, et pourquoi

| Brique             | Version   | Raison                                                                 |
| ------------------ | --------- | ---------------------------------------------------------------------- |
| PHP                | 8.4       | Image `dunglas/frankenphp:1-php8.4-bookworm` déjà présente localement  |
| Symfony            | 7.4.*     | LTS. 8.x n'a pas été essayé                                            |
| API Platform       | ^4 (4.4.3) | Version 4 demandée                                                    |
| Doctrine ORM / DBAL | 3.7 / 4.5 | Versions résolues par Composer                                        |
| PostgreSQL         | 17-alpine | Demandé, image déjà présente                                           |
| PHPUnit            | 13.4      | Version installée par la recette Flex                                  |
| PHPStan            | 2.3, niveau `max` | Projet neuf, sans dette : le niveau le plus strict ne coûte rien à tenir |

## Dépendances ajoutées, et pourquoi

- `symfony/twig-bundle`, `symfony/asset` : Swagger UI sur `/api/docs`.
- `phpstan/phpdoc-parser`, `phpdocumentor/type-resolver` : sans les deux, Symfony n'enregistre pas `PhpStanExtractor`, PropertyInfo ignore les `@param list<X>` et l'OpenAPI type les tableaux imbriqués (`items`, `facets`, `children`) en `(string | null)[]`.
- `ext-intl` : tri des noms en collation française (`\Collator`).
- `sentry/sentry-symfony` (5.14, SDK `sentry/sentry` 4.34) : suivi des erreurs vers GlitchTip, qui parle le protocole Sentry (section 16 de `SPEC-COMMUNE.md`). Environ 1,8 Mo dans `vendor/sentry`, plus `guzzlehttp/psr7`, `psr/http-message`, `psr/http-factory`, `symfony/psr-http-message-bridge`, `symfony/options-resolver` et `jean85/pretty-package-versions`. Neutre sans `GLITCHTIP_DSN` : le transport HTTP du SDK n'envoie rien quand le DSN est vide. `send_default_pii: false`, tracing coupé, corps de requête jamais envoyés (`max_request_body_size: none`). Le filtre `before_send` (`Shared/Infrastructure/Observability/IgnoreExpectedErrors`) écarte toute réponse 4xx (404, 405, 422, exceptions de `exception_to_status`) et `X-Scenario: error` : seules les 5xx réelles et les exceptions non gérées partent. Le bundle est déclaré à la main dans `config/bundles.php`, sa recette Flex étant dans le dépôt contrib (non autorisé). En test, `register_error_handler: false` et un client HTTP factice (`tests/Support/RecordingSentryHttpClient`) : aucune instance réelle n'est contactée.
- `deptrac/deptrac` (dev) : vérifie les règles de dépendance entre modules et couches (`deptrac.yaml`, `make arch`). Un `grep` ne voit pas les noms qualifiés ni les imports indirects. `make arch-selftest` écrit une violation volontaire (`Catalog\Domain` vers `Catalog\Infrastructure`), exige que deptrac échoue sur elle, puis retire le fichier : une règle qu'aucun test ne viole peut être débranchée sans bruit. Les deux tournent dans `make qa`, donc en CI.
- `doctrine/doctrine-fixtures-bundle` (dev) : `make db-reset` et la base de test.
- `symfony/browser-kit`, `symfony/http-client` (dev) : requis par `ApiTestCase`.
- Extensions PHPStan incluses à la main dans `phpstan.dist.neon`, sans `phpstan/extension-installer`.

## Pièges rencontrés

- **CSP absente des erreurs en debug** : `ErrorListener::removeCspHeader` de Symfony retire la CSP des réponses d'erreur quand `kernel.debug` est vrai (dev et test). En prod, la 404 la porte. Le test ne vérifie donc que les réponses réussies. Et le `Content-Type` d'une page Twig n'est posé qu'à l'envoi : l'abonné le déduit du format de la requête.
- **Recette Flex de doctrine-bundle** : elle ajoute un service `database` en PostgreSQL 16 dans `compose.yaml` et un `compose.override.yaml` qui publie le port 5432 sur un port aléatoire. Le projet n'en garde aucun : surveiller après tout `composer require`.
- **X-Scenario et la 500 en HTML** : la sous-requête qu'API Platform lance pour rendre l'erreur reprend les en-têtes, repasse dans le provider décoré et relance l'exception. Sans garde, Symfony retombe alors sur sa page HTML : d'où le test `request === mainRequest` dans `ScenarioProvider`.
- **`#[When]` ne supprime pas la définition** : hors environnement ciblé, la classe reste dans le `ContainerBuilder` avec le tag `container.excluded`, retiré à la compilation. `ScenarioRegistrationTest` vérifie le tag, pas seulement `hasDefinition()`.
- **`ApiTestCase` et `$alwaysBootKernel`** : sans `protected static ?bool $alwaysBootKernel = false;`, API Platform émet une dépréciation et `failOnDeprecation` fait échouer la suite.
- **Journaux dans PHPUnit** : sans Monolog, le logger par défaut écrit les 422 et 500 provoquées par les tests sur la sortie d'erreur. En test, `logger` est un `NullLogger` (`config/services.yaml`).
- **`Location` du 201** : API Platform renvoie `/api/contact_messages/{id}`, qui répond 404 (opération non exposée, ajoutée seulement pour générer l'IRI). À ne pas suivre côté front.
- **OpenAPI 3.2.0** : API Platform 4.4 exporte en 3.2. `openapi-typescript` 7.13 (version des boilerplates) le génère sans erreur, vérifié.
- **Unicode échappé en JSON simple** : `application/json` renvoie `é`, le JSON-LD non. Les deux sont du JSON valide.
- **PHPStan sur `public/index.php`** : `$context` est `mixed` au niveau max, et PHP-CS-Fixer transforme le `@var` en commentaire simple. PHPStan analyse `src/` et `tests/` seulement, les points d'entrée du squelette restent tels quels.
- **Validation implicite des `QueryParameter`** : API Platform 4.4 déduit des contraintes du `schema` (`minimum`, `maximum`, `enum` sur un scalaire) et répond 422 en anglais. L'`enum` des éléments d'un tableau n'est pas validé. Les bornes sont donc dans la description, pas dans le schéma.
- **Paramètre tableau documenté deux fois** : une clé `exposure[]` produit `exposure[]` et `exposure[][]` dans l'OpenAPI, une clé `exposure` produit `exposure` et `exposure[]`. La clé `exposure` avec `castToArray: true` ne documente que `exposure[]` et accepte aussi `exposure=ombre`.
- **`parent: null` absent du JSON** : API Platform saute les valeurs nulles par défaut. `CategoryResource` force `skip_null_values: false`, sinon la racine n'a pas de clé `parent` alors que le schéma la déclare requise.
- **`composer validate --strict`** exige `name` et `description`, même pour un projet.
- **Port 8090 lié à `127.0.0.1`** : un front servi par Docker passe par le réseau `boilerplate-api-platform_default` (`http://php:8080`), pas par `localhost`.
