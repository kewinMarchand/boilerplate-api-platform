# boilerplate-api-platform

API de test commune aux cinq boilerplates front (Next, Nuxt, SvelteKit, Symfony Twig, HTML vanilla). Elle sert des tâches, des articles, un formulaire de contact, un catalogue à facettes avec ses fiches produit et ses produits à la une (le carrousel de l'accueil, [ADR 0004](doc/adr/0004-carrousel-de-produits-a-la-une.md)), les pages légales, l'identité du site (nom, accroche, logo, favicons, image de partage, couleurs) et le référencement des pages fixes, dans une ou plusieurs langues (section « Langues du contenu »), avec des données réalistes, en JSON simple ou en JSON-LD, et sait simuler une erreur serveur ou une liste vide pour tester les trois états des fronts.

| Ressource        | Opération                    | Ce qu'elle renvoie                                                 |
| ---------------- | ---------------------------- | ------------------------------------------------------------------ |
| `Task`           | `GET /api/tasks`             | Les 3 tâches de démonstration (`id`, `title`, `done`)              |
| `Article`        | `GET /api/articles`          | 3 articles (`id`, `slug`, `title`, `excerpt`, `publishedAt`, `image`, `section`, `format`, `audio`, `speaker`), du plus récent au plus ancien ; filtres `section` et `format` (slug, inconnu : liste vide) |
| `ArticleDetail`  | `GET /api/articles/{slug}`   | fiche d'un article : les mêmes champs, plus `html` (texte saisi en Markdown dans l'admin, converti en HTML), `metaTitle`, `metaDescription` et `alternates` (versions dans les langues activées) ; 404 sinon |
| `ArticleTerm`    | `GET /api/article-sections`, `GET /api/article-formats` | Rubriques et formats d'article (`slug`, `name`), par position, même sans article ([ADR 0010](doc/adr/0010-rubriques-formats-et-audio-des-articles.md)) |
| `ContactMessage` | `POST /api/contact_messages` | 201 et le message enregistré puis envoyé par mail, ou 422 avec les violations en français ; `product` facultatif (slug) pour une demande de renseignement ([ADR 0014](doc/adr/0014-demande-de-renseignement-envoyee-par-mail.md)) |
| `Category`       | `GET /api/categories`        | L'arbre des 14 catégories du catalogue (`slug`, `name`, `parent`, `children`, `alternates`) |
| `Product`        | `GET /api/products`          | Une page des 24 produits, filtrée et triée (tableau) |
| `ProductFacets`  | `GET /api/product-facets`    | Le total des produits filtrés et les compteurs de facettes (dont les attributs filtrables), avec les mêmes filtres |
| `ProductDetail`  | `GET /api/products/{slug}`   | fiche d'un produit : les champs de la liste, plus `categoryPath` (catégories de la racine à la sienne, `slug` et `name`), `description` (texte brut, paragraphes séparés par une ligne vide, chaîne vide si aucune), `gallery` (8 images au plus, dans l'ordre, [ADR 0009](doc/adr/0009-galerie-et-sources-plus-petites-des-produits.md)), `metaTitle`, `metaDescription` et `alternates` ; 404 sinon |
| `Product`        | `GET /api/featured-products` | Les produits « À la une » (case de l'admin), par nom : le carrousel de l'accueil |
| `LegalPage`      | `GET /api/legal_pages/{slug}` | `mentions-legales`, `donnees-personnelles` ou `accessibilite` : `title`, `introduction`, `sections` (`title`, `html`) et `alternates` ; 404 sinon |
| `SiteIdentity`   | `GET /api/site-identity`     | L'identité du site saisie dans l'admin ([ADR 0006](doc/adr/0006-identite-du-site-administree-dans-l-api.md)) : `name`, `tagline`, `logo` (image ou `null`), `shareImage` (image 1200 × 630 ou `null`, [ADR 0011](doc/adr/0011-metadonnees-administrables.md)), `icon` (`null` ou `png32`, `png180`, `png192`, `png512`, [ADR 0012](doc/adr/0012-favicons-administrables.md)), `defaultColorScheme` (`auto`, `light`, `dark`) , `theme` (`null` sans couleurs saisies, sinon `light` et `dark` : les douze jetons de couleur de marque de boilerplates-ui, calculés par l'API ; en sombre, couleur éclaircie jusqu'à 4,5:1, [ADR 0008](doc/adr/0008-couleur-de-marque-sombre-a-4-5.md)) `salesEnabled` (vente en ligne ; `false` : ni prix ni panier dans les fronts, ADR 0014) et `locales` (langues du site, la langue par défaut en premier, `["fr"]` par défaut) |
| `PageMeta`       | `GET /api/page-metas`        | Titre et description du référencement des pages fixes (`home`, `catalog`, `blog`, `contact`, `sitemap`, `legal-notice`, `privacy`, `accessibility`), toutes les clés, chaînes vides si non saisies (ADR 0011) |

Tout est en lecture seule sauf le contact (un `POST` répond 405). Les collections ne sont pas paginées, sauf les produits qui ont leur propre pagination.

## Stack

| Brique                              | Rôle                                                  |
| ----------------------------------- | ----------------------------------------------------- |
| PHP 8.5, FrankenPHP 1 (Caddy)       | Serveur HTTP et PHP dans un seul conteneur            |
| Symfony 8.1                         | Framework                                             |
| API Platform 5.0                    | Ressources, formats, OpenAPI, erreurs `problem+json`  |
| Doctrine ORM 3, Migrations, Fixtures | Persistance, schéma versionné, données de démonstration |
| Symfony Mailer, Mailpit (dev)       | Envoi des messages de contact, capturés par Mailpit en dev |
| PostgreSQL 18                       | Base de données, non exposée sur la machine           |
| nelmio/cors-bundle                  | CORS pour les origines locales des fronts             |
| PHPUnit 13 + ApiTestCase            | Tests fonctionnels de chaque endpoint                 |
| PHPStan 2 (niveau max), PHP-CS-Fixer | Analyse statique et format                            |

## Prérequis

- Docker avec Compose v2, `make` et git. Rien d'autre : PHP, Composer et PostgreSQL tournent dans les conteneurs.
- Les ports 8090 (API) et 8026 (Mailpit) libres sur `127.0.0.1`.

## Démarrage

```sh
git clone https://github.com/kewinMarchand/boilerplate-api-platform.git
cd boilerplate-api-platform
make install    # image, conteneurs, dépendances Composer
make db-reset   # base, migrations, fixtures
```

L'API répond sur http://localhost:8090/api. La documentation interactive (Swagger UI) est sur http://localhost:8090/api/docs dans un navigateur. Les mails envoyés en dev (messages de contact) sont capturés par Mailpit, sur http://localhost:8026 : aucun n'est délivré.

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

**Demande de renseignement** (section 27 de `SPEC-COMMUNE.md`, ADR 0014) : le champ facultatif `product` porte le slug d'un produit (`null` ou absent pour un message simple). Un slug inconnu renvoie 422 sur `product`, « Ce produit n'existe pas. ».

**Envoi par mail** : une fois enregistré, le message part par SMTP (`MAILER_DSN`) vers l'« Adresse qui reçoit les messages » de la fiche « Identité du site » de l'admin (`contact@example.com` dans les fixtures), jamais exposée par l'API. Expéditeur `MAILER_FROM`, `Reply-To` : l'adresse du visiteur. Sujet « Demande de renseignement : <nom du produit> » ou « Message de contact : <nom saisi> », corps en texte brut :

```text
Nom : Camille Martin
Adresse : camille.martin@example.fr
Produit : Monstera deliciosa (monstera-deliciosa)

Message :
Bonjour, ce produit est-il disponible ?
```

La ligne `Produit` n'apparaît qu'avec un produit. Un envoi en échec est journalisé, le message reste enregistré et la réponse reste 201. En dev, le mail arrive dans Mailpit (http://localhost:8026, ou `curl localhost:8026/api/v1/messages`) ; en test, le transport `null://null` de Symfony (aucun mail réel). En production, renseigner `MAILER_DSN` (`smtp://utilisateur:motdepasse@serveur:587`) et `MAILER_FROM` dans `.env.local` ou l'environnement.

### Catalogue

Jardinerie tropicale fictive : 3 catégories racines, 14 catégories sur 3 niveaux, 24 produits dans les feuilles, deux attributs configurables ([ADR 0007](doc/adr/0007-attributs-de-produit-configurables.md)), deux galeries (`monstera-deliciosa`, 2 images ; `strelitzia-reginae`, 3 images). `GET /api/categories` renvoie les racines, chacune avec ses `children` imbriqués (`parent` vaut `null` à la racine).

`GET /api/products` renvoie une page de produits, sous la forme d'un **tableau** comme les autres collections. Le total et les compteurs de facettes sont une ressource à part, `GET /api/product-facets`, qui prend les mêmes filtres ([ADR 0005](doc/adr/0005-collection-de-produits-et-facettes-a-part.md)) :

```json
[{ "id": 1, "slug": "monstera-deliciosa", "name": "Monstera deliciosa", "categorySlug": "monstera", "price": 3490, "exposure": "mi-ombre", "size": "M", "inStock": true, "image": { … }, "attributes": [{ "code": "feuillage", "label": "Feuillage", "type": "choice", "value": "persistant", "valueLabel": "Persistant", "unit": null }, { "code": "hauteur-adulte", "label": "Hauteur adulte", "type": "number", "value": "300", "valueLabel": "300", "unit": "cm" }] }, …]
```

```json
{
  "totalItems": 24,
  "exposure": [{ "value": "soleil", "count": 14 }, { "value": "mi-ombre", "count": 7 }, { "value": "ombre", "count": 3 }],
  "size": [{ "value": "S", "count": 6 }, { "value": "M", "count": 9 }, { "value": "L", "count": 9 }],
  "category": [{ "slug": "plantes-interieur", "name": "Plantes d’intérieur", "count": 12 }, …],
  "attributes": [{ "code": "feuillage", "label": "Feuillage", "values": [{ "value": "persistant", "label": "Persistant", "count": 6 }, { "value": "caduc", "label": "Caduc", "count": 2 }] }]
}
```

En JSON-LD (`Accept: application/ld+json`), la liste est une `Collection` Hydra : `totalItems` filtré, `member` (chaque produit a son IRI `/api/products/{slug}`) et `view` (liens des pages).

| Paramètre | Effet |
| --------- | ----- |
| `category` | Slug : produits de toute la branche. Slug inconnu : 404 (`Catégorie introuvable : <slug>`) |
| `exposure[]` | `soleil`, `mi-ombre`, `ombre`, clé répétée, valeurs combinées en OU |
| `size[]` | `S`, `M`, `L`, clé répétée, valeurs combinées en OU |
| `priceMin`, `priceMax` | Bornes incluses, en **centimes** |
| `inStock` | `true` ou `1` : produits en stock seulement |
| `attributes[<code>][]` | Attributs filtrables (type `choice`) : `attributes[feuillage][]=persistant`, clé répétée, valeurs d'un même attribut en OU, attributs entre eux en ET |
| `order[price]`, `order[name]` | Liste seulement. `asc` ou `desc`. Le prix l'emporte si les deux sont donnés. Sans tri : ordre de pertinence (ordre des fixtures). Tri par nom en collation française |
| `page`, `itemsPerPage` | Liste seulement. Page à partir de 1, 12 produits par défaut, 48 au plus. Au-delà de la dernière page, le tableau est vide |

Les facettes sont **disjonctives** : le compteur d'une valeur d'exposition applique tous les filtres actifs sauf l'exposition, idem pour la taille. `category` liste les sous-catégories directes de la catégorie filtrée (les racines sans `category`, vide sur une feuille), comptées avec tous les autres filtres. Toutes les expositions et tailles sont toujours présentes, y compris à 0. `attributes` liste les attributs filtrables portés par au moins un produit de la branche, avec toutes leurs valeurs (y compris à 0), comptées avec tous les filtres sauf celui de leur propre attribut.

**Attributs configurables** : les caractéristiques propres à un client (diamètre d'une jante, kilométrage d'un véhicule) se définissent dans l'admin, section « Attributs » (code, libellé, type `choice`, `number` ou `text`, unité, filtre du catalogue, position, valeurs d'une liste), sans changer le code ; le formulaire du produit gagne un champ par attribut. Chaque produit et chaque fiche exposent `attributes`, les attributs renseignés par position (`value` en chaîne, `valueLabel` = libellé de la valeur pour une liste, `unit` à `null` sans unité). Fixtures : `feuillage` (liste filtrable, 8 produits) et `hauteur-adulte` (nombre, en cm, 6 produits).

Une valeur invalide (exposition inconnue, prix non numérique, page hors bornes, attribut ou valeur inconnus) est **ignorée**, comme dans l'URL des fronts : pas de 422.

### Blog et référencement

Articles des fixtures : deux rubriques (« Conseils », 2 articles ; « Actualités », 1 article) et un format (« Guide », 1 article), un fichier audio de démonstration (bip de 4 secondes généré par ffmpeg, sans droits, `fixtures/audio/article-demo.mp3`) avec son intervenant sur `un-carrousel-accessible-sans-dependance`. Rubriques et formats s'administrent dans les sections du même nom ; supprimer un terme porté par un article est refusé (« La rubrique Conseils est utilisée par 2 articles. »).

Le référencement est facultatif partout (section 26 de `SPEC-COMMUNE.md`, ADR 0011) : chaîne vide, le front garde sa valeur. Section « Référencement » de l'admin : une fiche par page fixe (titre de 60 caractères, description de 160). Produits et articles ont les mêmes champs dans leur formulaire. Fixtures : seule la description de la page `blog` est saisie.

### Langues du contenu

Section 29 de `SPEC-COMMUNE.md`, [ADR 0015](doc/adr/0015-contenu-multilingue-et-admin-bilingue.md). Un site n'a qu'une langue, le français, tant que l'admin n'en active pas d'autre (fiche « Identité du site » : langue par défaut et autres langues). Un site monolingue garde exactement le contrat d'avant, avec `locales: ["fr"]` et `alternates: []` en plus.

- **Langue demandée** : le paramètre `locale` (`?locale=en`) s'il désigne une langue activée, sinon la première langue activée d'`Accept-Language` (par poids, région ignorée : `en-GB` vaut `en`), sinon la langue par défaut. Toute réponse sous `/api/` porte `Content-Language` et `Vary: Accept-Language`.
- **Repli champ par champ** : un champ non traduit prend la valeur de la langue par défaut. Champs traduisibles : nom et accroche du site ; nom, slug, description, métadonnées et textes de remplacement des produits ; nom et slug des catégories ; libellé, unité et libellés des valeurs des attributs ; slug, titre, chapô, texte, intervenant, métadonnées et texte de remplacement des articles ; nom et slug des rubriques et formats ; titre et texte des pages légales ; titre et description des pages fixes. Prix, stock, images, dates et valeurs numériques sont communs.
- **Slug par langue** : celui de la langue, ou celui de la langue par défaut s'il n'est pas traduit. Un slug d'une autre langue répond 404 (`/api/products/monstera-deliciosa?locale=en` quand le produit a le slug anglais `swiss-cheese-plant`). Les filtres `category`, `section` et `format` prennent aussi le slug de la langue. Le slug des pages légales est commun à toutes les langues.
- **`alternates`** : `[{locale, slug}]`, une entrée par langue activée, langue demandée comprise, pour les `hreflang` et le sélecteur de langue des fronts.
- **Cas bilingue de test** : `X-Scenario: bilingual` (dev et test) active aussi l'anglais. Les fixtures portent alors leurs traductions anglaises : accroche du site ; catégories `plantes-interieur` (`indoor-plants`, « Indoor plants ») et `feuillages` (`foliage-plants`, « Foliage plants ») ; produit `monstera-deliciosa` (`swiss-cheese-plant`, « Swiss cheese plant », titre de référencement) ; attributs `feuillage` (« Foliage », « Evergreen », « Deciduous ») et `hauteur-adulte` (« Mature height ») ; article `le-mode-accessibilite-renforcee` (`enhanced-accessibility-mode`, titre et chapô) ; rubrique `conseils` (`tips`, « Tips ») ; titre de `mentions-legales` (« Legal notice ») ; description de la page fixe `blog`. Le reste est en repli sur le français.

```sh
curl -i 'http://localhost:8090/api/products/swiss-cheese-plant' -H 'X-Scenario: bilingual' -H 'Accept-Language: en'
```

L'interface de l'admin est en français ou en anglais (sélecteur dans le menu des réglages, choix gardé pour la session), indépendamment des langues du contenu. Sur un site de plusieurs langues, les fiches traduisibles ont un onglet par langue.

### Simuler un état : en-tête `X-Scenario`

En dev et en test uniquement. En prod, la classe n'est pas enregistrée et l'en-tête n'a aucun effet.

| Valeur  | Effet                                                                |
| ------- | -------------------------------------------------------------------- |
| `error` | 500 en `application/problem+json`, sur toutes les opérations         |
| `bilingual` | Le site active aussi l'anglais pour la requête : les traductions anglaises des fixtures servent (section « Langues du contenu ») |
| `empty` | Collection vide (`[]`) sur les tâches, articles, rubriques, formats, référencement des pages fixes, catégories et produits. Sur `/api/product-facets` : total et compteurs à 0, `attributes` vide. Sur `/api/site-identity` : l'identité par défaut (celle des fixtures) |

Toute autre valeur est ignorée.

```sh
curl -i http://localhost:8090/api/tasks -H 'X-Scenario: empty'
```

### CORS

Les origines autorisées sont une expression régulière dans `CORS_ALLOW_ORIGIN` (`.env`, surchargeable dans `.env.local` ou par variable d'environnement). Par défaut, celle de la section 7 de la spec commune (`~/Applications/boilerplates-commun/SPEC-COMMUNE.md`, registre unique des ports) : `http://localhost` ou `http://127.0.0.1` sur les ports de développement des fronts (3000, 3010, 4321, 5173, 8095 et son repli 18095) et sur les blocs de tests et d'outils 4000 à 4199. En-têtes autorisés : `Content-Type`, `Accept`, `X-Scenario`.

### Générer les types TypeScript

Le schéma OpenAPI est servi à **`http://localhost:8090/api/docs.jsonopenapi`** (OpenAPI 3.2, `application/vnd.openapi+json`). Il est aussi versionné dans **`openapi.json`** à la racine, pour générer les types sans lancer l'API. Côté front, `make api-types` génère les types depuis le schéma désigné par `API_SCHEMA_URL` (voir le README du front).

Les schémas utiles côté front sont `Task`, `Article`, `ContactMessage`, `ConstraintViolation`, `Category`, `Product`, `ProductFacets`, `FacetValue`, `CategoryFacetValue`, `ProductDetail`, `CategoryLink`, `LegalPage`, `SiteIdentity`, `SiteTheme`, `ThemeTokens`, `SiteIcon`, `ArticleDetail`, `ArticleTerm`, `ArticleTermLink`, `PageMeta`, `ImageView`, `ImageSource` et `AudioView` (les variantes `.jsonld` concernent le JSON-LD). `make qa` échoue si `openapi.json` est périmé : relancer `make openapi` après toute modification d'une ressource.

### Images

Diapositives, produits et articles portent une image envoyée par l'admin ([ADR 0002](doc/adr/0002-images-envoyees-par-l-admin.md)). Le champ `image` vaut un objet, ou `null` pour une ligne d'avant l'envoi des images :

```json
{
  "alt": "Monstera deliciosa",
  "width": 800,
  "height": 800,
  "sources": [
    { "type": "image/avif", "srcset": "http://localhost:8090/media/<empreinte>-400.avif 400w, http://localhost:8090/media/<empreinte>-800.avif 800w" },
    { "type": "image/webp", "srcset": "http://localhost:8090/media/<empreinte>-400.webp 400w, http://localhost:8090/media/<empreinte>-800.webp 800w" }
  ],
  "src": "http://localhost:8090/media/<empreinte>-400.webp"
}
```

Le front pose `sources` dans un `<picture>`, `src`, `alt`, `width` et `height` sur l'`<img>`, et n'ajoute que `sizes`. `alt` vide : image décorative. Largeurs : article 480 et 960 (16:9), produit 400, 640 et 800 (carré, ADR 0003), logo 200 et 400 (sans recadrage : `height` suit le ratio du fichier, transparence gardée, ADR 0006), image de partage 1200 (1200 × 630). Une source n'est jamais agrandie (ADR 0009) : un produit accepte une source de 400 px de large une fois recadrée, et une source de 600 px donne les variantes 400 et 600, `width` et `height` étant ceux de la plus grande. Les favicons (`icon`) sont des PNG carrés de 32, 180, 192 et 512 px.

**Audio** : un article peut porter un fichier MP3, M4A ou OGG (100 Mo au plus, type et durée lus dans le contenu à l'envoi, ADR 0010), servi tel quel sous `/media/<empreinte>.<extension>` : `audio` vaut `{ "src": "…", "type": "audio/mpeg", "durationSeconds": 4 }` ou `null`. Les adresses sont absolues, préfixées par `MEDIA_BASE_URL` (origine publique de l'API, `http://localhost:8090` par défaut) : la CSP du front doit autoriser cette origine dans `img-src`. Les fichiers ne changent jamais (leur nom porte l'empreinte du contenu) et sont servis en `Cache-Control: immutable`.

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
  Blog/                     articles, rubriques et formats (ArticleTerm), audio et intervenant (ADR 0010)
  Contact/                  messages de contact, ressource + processor
  Catalog/                  catégories et produits
    Domain/                 Price, CategoryTree (value objects), ProductAttribute (attributs
                            configurables), ProductSearch : filtre, facettes disjonctives,
                            tri, pagination (PHP pur)
    Application/            SearchProducts : charge par les ports, délègue à ProductSearch ;
                            écritures de l'admin (catégories, produits, attributs)
  Site/                     identité du site (ADR 0006)
    Domain/                 SiteIdentity, HexColor, BrandColors (règles de contraste), BrandPalette
                            et ColorShades (jetons clair et sombre)
    Application/            GetSiteIdentity (valeurs du modèle sans ligne), UpdateSiteIdentity
  Content/                  pages légales, référencement des pages fixes (PageMeta, ADR 0011)
  Media/                    images (variantes AVIF, WebP, PNG des favicons) et fichiers audio
                            (HeaderAudioInspector : format et durée lus dans les en-têtes, PHP seul)
  Shared/
    Domain/                 Translatable (traductions d'un agrégat, repli champ par champ), TranslatedSlugs,
                            RuleViolation (règle enfreinte, modèle de message traduit par l'admin)
    Infrastructure/Locale/  langue demandée (ContentLocale), Content-Language, X-Scenario: bilingual (ADR 0015)
    Infrastructure/ApiPlatform/Scenario/   en-tête X-Scenario
tests/
  Unit/                     value objects et cas d'utilisation du catalogue, sans framework
  Functional/               un fichier par ressource, plus X-Scenario et CORS
  Integration/              enregistrement de X-Scenario selon l'environnement, encodeur GD,
                            lecture des en-têtes audio (fichiers de tests/Fixtures/audio)
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

`make docker-build-prod` construit une image multi-étapes : dépendances sans `require-dev`, `.env` compilé (`composer dump-env prod`), cache préchauffé, utilisateur `www-data`, port 8080. Elle attend `APP_SECRET`, `DATABASE_URL` et `MEDIA_BASE_URL` (origine publique de l'API) en variables d'environnement. Les migrations se lancent à part (`bin/console doctrine:migrations:migrate`).

**Images** : les variantes envoyées par l'admin sont écrites dans `/app/var/media`, à monter sur un volume persistant (propriétaire `www-data`). Ce volume fait partie des données du projet : il se sauvegarde et se restaure avec la base. Caddy le sert sous `/media/` (`docker/media.caddy`). Le volume porte aussi les fichiers audio des articles. L'image accepte des envois de 100 Mo (`docker/uploads.ini` : images de 10 Mo, fichiers audio de 100 Mo).

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
| PostgreSQL | utilisateur, mot de passe et base (`app`) dans `compose.yaml` et `.env`. La version (18) dans l'image et dans `serverVersion` |
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
   - `tests/Functional/ScenarioHeaderTest.php` : supprimer les tests sur `/api/product-facets` et `/api/contact_messages`, et les entrées `articles`, `catégories`, `produits` et `produits à la une` de `collections()`.
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
