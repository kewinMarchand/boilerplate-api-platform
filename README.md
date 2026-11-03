# boilerplate-api-platform

API de test commune aux cinq boilerplates front (Next, Nuxt, SvelteKit, Symfony Twig, HTML vanilla). Elle sert des tâches, des articles, un formulaire de contact, un catalogue à facettes, le carrousel de l'accueil et les pages légales, avec des données réalistes, en JSON simple ou en JSON-LD, et sait simuler une erreur serveur ou une liste vide pour tester les trois états des fronts.

| Ressource        | Opération                    | Ce qu'elle renvoie                                                 |
| ---------------- | ---------------------------- | ------------------------------------------------------------------ |
| `Task`           | `GET /api/tasks`             | Les 3 tâches de démonstration (`id`, `title`, `done`)              |
| `Article`        | `GET /api/articles`          | 3 articles (`id`, `slug`, `title`, `excerpt`, `publishedAt`), du plus récent au plus ancien |
| `ContactMessage` | `POST /api/contact_messages` | 201 et le message enregistré, ou 422 avec les violations en français |
| `Category`       | `GET /api/categories`        | L'arbre des 14 catégories du catalogue (`slug`, `name`, `parent`, `children`) |
| `ProductList`    | `GET /api/products`          | Une page des 24 produits, filtrée et triée, avec le total et les compteurs de facettes |
| `Slide`          | `GET /api/slides`            | Les 5 diapositives du carrousel (`id`, `slug`, `title`, `text`, `image`), dans l'ordre |
| `LegalPage`      | `GET /api/legal_pages/{slug}` | `mentions-legales`, `donnees-personnelles` ou `accessibilite` : `title`, `introduction` et `sections` (`title`, `html`) ; 404 sinon |

Tout est en lecture seule sauf le contact (un `POST` répond 405). Les collections ne sont pas paginées, sauf les produits qui ont leur propre pagination.

## Stack

| Brique                              | Rôle                                                  |
| ----------------------------------- | ----------------------------------------------------- |
| PHP 8.5, FrankenPHP 1 (Caddy)       | Serveur HTTP et PHP dans un seul conteneur            |
| Symfony 8.1                         | Framework                                             |
| API Platform 5.0                    | Ressources, formats, OpenAPI, erreurs `problem+json`  |
| Doctrine ORM 3, Migrations, Fixtures | Persistance, schéma versionné, données de démonstration |
| PostgreSQL 17                       | Base de données, non exposée sur la machine           |
| nelmio/cors-bundle                  | CORS pour les origines locales des fronts             |
| PHPUnit 13 + ApiTestCase            | Tests fonctionnels de chaque endpoint                 |
| PHPStan 2 (niveau max), PHP-CS-Fixer | Analyse statique et format                            |

## Prérequis

- Docker avec Compose v2, `make` et git. Rien d'autre : PHP, Composer et PostgreSQL tournent dans les conteneurs.
- Le port 8090 libre sur `127.0.0.1`.

## Démarrage

```sh
git clone https://github.com/kewinMarchand/boilerplate-api-platform.git
cd boilerplate-api-platform
make install    # image, conteneurs, dépendances Composer
make db-reset   # base, migrations, fixtures
```

L'API répond sur http://localhost:8090/api. La documentation interactive (Swagger UI) est sur http://localhost:8090/api/docs dans un navigateur.

Le projet Compose s'appelle `boilerplate-api-platform` : ses conteneurs, son réseau et son volume (`boilerplate-api-platform_database_data`) ne touchent à aucun autre projet. Le conteneur PHP tourne avec l'UID de l'utilisateur courant, les fichiers créés dans le dépôt lui appartiennent.

## Commandes

`make help` liste toutes les commandes. Les principales :

| Commande        | Effet                                                                     |
| --------------- | ------------------------------------------------------------------------- |
| `make install`  | Construit l'image, lance les conteneurs, installe les dépendances         |
| `make up` / `make down` | Lance ou arrête les conteneurs (la base est conservée)            |
| `make db-reset` | Recrée la base de dev, joue les migrations, charge les fixtures           |
| `make qa`       | Lint, architecture, format, PHPStan, contrôle de `openapi.json`, tests    |
| `make arch`     | deptrac : règles de dépendance entre modules et couches (`deptrac.yaml`)  |
| `make arch-selftest` | Écrit une violation volontaire, exige l'échec de deptrac, puis la retire |
| `make lint`     | `composer validate`, `lint:container`, `lint:yaml`, `doctrine:schema:validate` |
| `make format`   | PHP-CS-Fixer en écriture (`make format-check` pour vérifier)              |
| `make typecheck` | PHPStan, niveau max, extensions Symfony et Doctrine                      |
| `make test`     | Recrée la base de test, puis PHPUnit                                      |
| `make openapi`  | Exporte le schéma OpenAPI dans `openapi.json`                             |
| `make logs` / `make sh` | Journaux des conteneurs, shell dans le conteneur PHP              |
| `make docker-build-prod` | Image de production (`boilerplate-api-platform:prod`)               |

## Consommer l'API depuis un front

### Format

Le **JSON simple** (`application/json`) est le format par défaut : une requête sans en-tête `Accept` (ou avec `Accept: */*`) le reçoit. Une collection est un tableau, sans enveloppe Hydra :

```sh
curl http://localhost:8090/api/tasks
# [{"id":1,"title":"Brancher la vraie API","done":false}, ...]
```

Le JSON-LD reste disponible avec `Accept: application/ld+json` (collection dans `member`, `totalItems`).

`publishedAt` est une date au format `AAAA-MM-JJ`. Les chaînes accentuées sont échappées (`é`) en JSON simple : c'est du JSON valide, tout décodeur les restitue.

### Formulaire de contact

```sh
curl -X POST http://localhost:8090/api/contact_messages \
  -H 'Content-Type: application/json' \
  -d '{"name":"Camille Martin","email":"camille.martin@example.fr","message":"Bonjour, je souhaite un devis."}'
```

Règles et messages, identiques au schéma zod des boilerplates (`contactSchema.ts`) :

| Champ     | Règle                       | Message                                                           |
| --------- | --------------------------- | ----------------------------------------------------------------- |
| `name`    | 2 caractères minimum, espaces ignorés | Indiquez votre nom (2 caractères minimum).              |
| `email`   | adresse valide              | Indiquez une adresse e-mail valide, par exemple nom@domaine.fr.   |
| `message` | 10 caractères minimum, espaces ignorés | Votre message doit contenir au moins 10 caractères.    |

Un payload invalide renvoie **422** en `application/problem+json`. Le tableau `violations` donne un message par champ, à afficher sous le champ correspondant (`propertyPath`) :

```json
{
  "status": 422,
  "violations": [
    { "propertyPath": "email", "message": "Indiquez une adresse e-mail valide, par exemple nom@domaine.fr.", "code": "bd79c0ab-ddba-46cc-a703-a7a4b08de310" }
  ],
  "detail": "email: Indiquez une adresse e-mail valide, par exemple nom@domaine.fr.",
  "title": "An error occurred"
}
```

`title` reste en anglais (valeur d'API Platform) : il ne doit pas être affiché, seuls les `message` le sont.

### Catalogue

Jardinerie tropicale fictive : 3 catégories racines, 14 catégories sur 3 niveaux, 24 produits dans les feuilles. `GET /api/categories` renvoie les racines, chacune avec ses `children` imbriqués (`parent` vaut `null` à la racine).

`GET /api/products` renvoie un **objet**, pas une collection :

```json
{
  "items": [{ "id": 1, "slug": "monstera-deliciosa", "name": "Monstera deliciosa", "categorySlug": "monstera", "price": 3490, "exposure": "mi-ombre", "size": "M", "inStock": true, "image": 1 }],
  "totalItems": 24, "page": 1, "itemsPerPage": 12, "totalPages": 2,
  "facets": {
    "exposure": [{ "value": "soleil", "count": 14 }, { "value": "mi-ombre", "count": 7 }, { "value": "ombre", "count": 3 }],
    "size": [{ "value": "S", "count": 6 }, { "value": "M", "count": 9 }, { "value": "L", "count": 9 }],
    "category": [{ "slug": "plantes-interieur", "name": "Plantes d’intérieur", "count": 12 }, ...]
  }
}
```

| Paramètre | Effet |
| --------- | ----- |
| `category` | Slug : produits de toute la branche. Slug inconnu : 404 (`Catégorie introuvable : <slug>`) |
| `exposure[]` | `soleil`, `mi-ombre`, `ombre`, clé répétée, valeurs combinées en OU |
| `size[]` | `S`, `M`, `L`, clé répétée, valeurs combinées en OU |
| `priceMin`, `priceMax` | Bornes incluses, en **centimes** |
| `inStock` | `true` ou `1` : produits en stock seulement |
| `order[price]`, `order[name]` | `asc` ou `desc`. Le prix l'emporte si les deux sont donnés. Sans tri : ordre de pertinence (ordre des fixtures). Tri par nom en collation française |
| `page`, `itemsPerPage` | Page à partir de 1, 12 produits par défaut, 48 au plus. Au-delà de la dernière page, `items` est vide |

Les facettes sont **disjonctives** : le compteur d'une valeur d'exposition applique tous les filtres actifs sauf l'exposition, idem pour la taille. `facets.category` liste les sous-catégories directes de la catégorie filtrée (les racines sans `category`, vide sur une feuille), comptées avec tous les autres filtres. Toutes les expositions et tailles sont toujours présentes, y compris à 0.

Une valeur invalide (exposition inconnue, prix non numérique, page hors bornes) est **ignorée**, comme dans l'URL des fronts : pas de 422. `image` est l'index du visuel partagé (`product-{1..12}`).

### Simuler un état : en-tête `X-Scenario`

En dev et en test uniquement. En prod, la classe n'est pas enregistrée et l'en-tête n'a aucun effet.

| Valeur  | Effet                                                                |
| ------- | -------------------------------------------------------------------- |
| `error` | 500 en `application/problem+json`, sur toutes les opérations         |
| `empty` | Collection vide (`[]`) sur les tâches, articles et catégories. Sur `/api/products` : `items` vide, totaux à 0, compteurs à 0 |

Toute autre valeur est ignorée.

```sh
curl -i http://localhost:8090/api/tasks -H 'X-Scenario: empty'
```

### CORS

Les origines autorisées sont une expression régulière dans `CORS_ALLOW_ORIGIN` (`.env`, surchargeable dans `.env.local` ou par variable d'environnement). Par défaut : `http://localhost` sur les ports 3000, 3010, 3100, 3102, 3110, 3111, 3112, 3120, 3121, 3122, 3130, 3133, 3150, 3151, 3200, 3210, 3220, 3230, 3250, 5173 et 8095 (développement, tests et Lighthouse des fronts). En-têtes autorisés : `Content-Type`, `Accept`, `X-Scenario`.

### Générer les types TypeScript

Le schéma OpenAPI est servi à **`http://localhost:8090/api/docs.jsonopenapi`** (OpenAPI 3.2, `application/vnd.openapi+json`). Il est aussi versionné dans **`openapi.json`** à la racine, pour générer les types sans lancer l'API. Côté front, `make api-types` génère les types depuis le schéma désigné par `API_SCHEMA_URL` (voir le README du front).

Les schémas utiles côté front sont `Task`, `Article`, `ContactMessage`, `ConstraintViolation`, `Category`, `ProductList`, `ProductItem`, `ProductFacets`, `FacetValue` et `CategoryFacetValue` (les variantes `.jsonld` concernent le JSON-LD). `make qa` échoue si `openapi.json` est périmé : relancer `make openapi` après toute modification d'une ressource.

### Front dans un conteneur Docker

L'API n'écoute que sur `127.0.0.1:8090`. Un front servi par Docker n'y accède pas par `localhost` : le brancher sur le réseau `boilerplate-api-platform_default` et viser `http://php:8080`, ou changer la publication du port dans `compose.yaml`.

## Architecture

Hexagonale légère. L'API est un seul contexte borné, découpé en un module par sous-domaine :

```
src/
  Task/                     tâches
    Domain/                 modèle (Task) et port (TaskRepository), sans framework
    Infrastructure/
      ApiPlatform/          ressource exposée (TaskResource) et son provider
      Doctrine/             adaptateur du port, mapping XML, fixtures
  Blog/                     articles, même découpage
  Contact/                  messages de contact, ressource + processor
  Catalog/                  catégories et produits
    Domain/                 Price, CategoryTree (value objects), ProductSearch : filtre,
                            facettes disjonctives, tri, pagination (PHP pur)
    Application/            SearchProducts : charge par les ports, délègue à ProductSearch
  Shared/Infrastructure/ApiPlatform/Scenario/   en-tête X-Scenario
tests/
  Unit/                     value objects et cas d'utilisation du catalogue, sans framework
  Functional/               un fichier par ressource, plus X-Scenario et CORS
  Integration/              enregistrement de X-Scenario selon l'environnement
```

Les règles sont vérifiées par deptrac (`make arch`) : `Domain` ne dépend que de lui-même, `Application` du `Domain` de son module, `Infrastructure` de son module, de `Shared` et des frameworks, et aucun module n'en importe un autre. Le domaine ne dépend ni de Doctrine ni d'API Platform : le mapping Doctrine est en XML dans l'infrastructure, et les ressources API Platform sont des classes à part, construites depuis le modèle (`fromModel`). La validation porte sur la ressource, frontière de l'API.

### Ajouter une ressource en lecture

1. Créer `src/<Module>/Domain/<Modele>.php` et l'interface du dépôt.
2. Ajouter le mapping `src/<Module>/Infrastructure/Doctrine/Mapping/<Modele>.orm.xml`, le dépôt Doctrine et la fixture.
3. Déclarer le mapping dans `config/packages/doctrine.yaml` et le dossier de ressources dans `config/packages/api_platform.yaml`.
4. Créer la ressource et son provider dans `Infrastructure/ApiPlatform/`.
5. `docker compose exec php bin/console doctrine:migrations:diff`, `make db-reset`, `make openapi`, puis un test dans `tests/Functional/`.

## Suivi des erreurs (GlitchTip)

Les erreurs inattendues (5xx réelles, exceptions non gérées) partent vers GlitchTip par le SDK Sentry quand `GLITCHTIP_DSN` est renseigné, dans `.env.local` jamais versionné. Vide (valeur par défaut), rien n'est envoyé. `GLITCHTIP_ENVIRONMENT` nomme l'environnement, l'environnement Symfony (`APP_ENV`) à défaut. Aucune donnée personnelle, aucun corps de requête, aucune trace de performance. Les réponses attendues (404, 405, 422 de validation, `X-Scenario`) ne sont jamais remontées.

## Production

`make docker-build-prod` construit une image multi-étapes : dépendances sans `require-dev`, `.env` compilé (`composer dump-env prod`), cache préchauffé, utilisateur `www-data`, port 8080. Elle attend `APP_SECRET` et `DATABASE_URL` en variables d'environnement. Les migrations se lancent à part (`bin/console doctrine:migrations:migrate`).

## Intégration continue

`.github/workflows/ci.yml` appelle les mêmes cibles que le poste de dev, donc les mêmes conteneurs : `make install`, `make db-reset`, puis `make qa`. Rien n'est installé sur le runner en dehors de Docker.

## Démarrer un projet à partir de ce dépôt

Le dépôt est un modèle (« template ») sur GitHub. Deux façons d'en partir, selon le lien que le nouveau projet doit garder avec celui-ci.

### Créer le dépôt

**Nouveau projet (voie recommandée)** : bouton « Use this template » sur GitHub, ou avec la CLI GitHub (`gh`, facultative) :

```sh
gh repo create mon-projet --template kewinMarchand/boilerplate-api-platform --private --clone
```

Le nouveau dépôt contient les fichiers de la branche `main`, avec un historique vierge et sans lien avec l'original.

**Fork, pour suivre les évolutions de ce dépôt ou y contribuer** (bouton « Fork » sur GitHub, ou avec `gh`) :

```sh
gh repo fork kewinMarchand/boilerplate-api-platform --clone
```

Le fork devient `origin` et le dépôt d'origine `upstream`. GitHub n'autorise pas un fork dans le compte qui possède déjà le dépôt : le propriétaire passe par le modèle ou par une organisation.

### Récupérer les mises à jour plus tard

Avec un fork, `upstream` existe déjà. Avec un dépôt créé depuis le modèle, l'ajouter une fois :

```sh
git remote add upstream https://github.com/kewinMarchand/boilerplate-api-platform.git
git fetch upstream
```

Puis, selon l'origine du dépôt :

- **Fork** : l'historique est commun, une fusion suffit.

  ```sh
  git merge upstream/main
  ```

- **Dépôt créé depuis le modèle** : les historiques n'ont aucun commit commun et `git merge upstream/main` refuse de fusionner. Reprendre les commits utiles un par un, puis résoudre les conflits sur les fichiers personnalisés :

  ```sh
  git log --oneline upstream/main
  git cherry-pick <commit>
  ```

### Personnaliser

| Sujet | Où |
| ----- | -- |
| Nom du projet | `name:` et `image:` dans `compose.yaml` (ils nomment aussi le réseau et le volume `<nom>_database_data`), étiquette de `make docker-build-prod` dans `Makefile`, titre du README, `LICENSE` |
| Paquet Composer | `name` et `description` de `composer.json`, puis `docker compose exec php composer update --lock` |
| Titre de l'API | `title`, `description` et `version` dans `config/packages/api_platform.yaml` |
| Ports | port publié `API_PORT` (8090 par défaut) dans `compose.yaml`, et la mention du port dans la cible `up` du `Makefile`. Le conteneur écoute sur 8080, PostgreSQL n'est pas publié |
| Variables d'environnement | `.env` (`DATABASE_URL`, `CORS_ALLOW_ORIGIN`, `DEFAULT_URI`), `.env.test`. Les surcharges locales vont dans `.env.local`, jamais versionné |
| Secrets | régénérer `APP_SECRET` dans `.env.dev` (valeur de développement versionnée). En production, `APP_SECRET` et `DATABASE_URL` viennent de l'environnement, rien n'est versionné |
| PostgreSQL | utilisateur, mot de passe et base (`app`) dans `compose.yaml` et `.env`. La version (17) dans l'image et dans `serverVersion` |
| Scénarios `X-Scenario` | `src/Shared/Infrastructure/ApiPlatform/Scenario/`. Pour s'en passer : supprimer ce dossier, `tests/Functional/ScenarioHeaderTest.php`, `tests/Integration/ScenarioRegistrationTest.php`, puis retirer `X-Scenario` de `config/packages/nelmio_cors.yaml` et de `config/packages/api_platform.yaml` |
| Contrat OpenAPI | `openapi.json` se régénère par `make openapi` et `make qa` vérifie qu'il est à jour. La comparaison `make contract` appartient au dépôt jumeau `boilerplate-hono` : elle ne concerne pas un projet qui n'a pas de seconde implémentation |
| CORS | origines locales dans `CORS_ALLOW_ORIGIN` (`.env`) |
| CI | `.github/workflows/ci.yml` appelle `make` : les identifiants PostgreSQL et la version de PHP viennent de `compose.yaml` et du `Dockerfile` |
| Documentation | ce README et `CLAUDE.md`, qui décrivent les modules d'exemple |

### Retirer les modules d'exemple

`Task`, `Blog`, `Contact` et `Catalog` sont des exemples. Garder au moins un module avec un `Domain` et une `Infrastructure` tant que le premier module du projet n'existe pas : `make arch-selftest` écrit sa violation volontaire dans un module réel. L'exemple ci-dessous garde `Task` et retire les trois autres.

1. Supprimer le code, les fixtures et les tests :

   ```sh
   rm -r src/Catalog src/Blog src/Contact tests/Unit
   rm tests/Functional/ProductApiTest.php tests/Functional/CategoryApiTest.php \
      tests/Functional/ArticleApiTest.php tests/Functional/ContactMessageApiTest.php
   ```

   Les fixtures sont dans `src/<Module>/Infrastructure/Doctrine/Fixtures/` et partent avec leur module.

2. Dans `config/packages/doctrine.yaml`, retirer les entrées `Catalog`, `Blog` et `Contact` de `mappings`.
3. Dans `config/packages/api_platform.yaml`, retirer leurs dossiers de `mapping.paths`, ainsi que `exception_to_status` (seule `CategoryNotFound` y figure).
4. Dans `deptrac.yaml`, retirer les couches `Catalog*`, `Blog*` et `Contact*` et leurs lignes de `ruleset`.
5. Dans `Makefile.qa.mk`, pointer `make arch-selftest` sur le module conservé : `ARCH_SELFTEST_FILE = src/Task/Domain/ArchSelftestViolation.php`, puis remplacer dans le `printf` et le `grep` de la cible `App\Catalog\Domain` par `App\Task\Domain`, `App\Catalog\Infrastructure\Doctrine\DoctrineProductRepository` par `App\Task\Infrastructure\Doctrine\DoctrineTaskRepository` (et le type du paramètre), `App\\Catalog\\Infrastructure` par `App\\Task\\Infrastructure`.
6. Adapter les tests transverses qui visent les ressources retirées :
   - `tests/Functional/ScenarioHeaderTest.php` : supprimer les tests sur `/api/products` et `/api/contact_messages`, et les entrées `articles` et `catégories` de `collections()`.
   - `tests/Functional/CorsTest.php` : faire porter le test de prévol sur `/api/tasks` avec la méthode `GET`.
7. Repartir d'une migration propre. La migration actuelle crée toutes les tables :

   ```sh
   rm migrations/Version*.php
   docker compose exec php bin/console doctrine:database:drop --force
   docker compose exec php bin/console doctrine:database:create
   docker compose exec php bin/console doctrine:migrations:diff
   make db-reset
   make openapi
   make qa
   ```

8. Mettre à jour ce README et `CLAUDE.md`, puis supprimer le module `Task` quand le premier module du projet le remplace dans `make arch-selftest`.

## Licence

MIT
