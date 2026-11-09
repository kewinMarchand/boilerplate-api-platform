# 0015 — Contenu multilingue activable, admin en français et en anglais

- **Statut** : Acceptée
- **Décideurs** : Kewin Marchand
- **Complète** : l'[ADR 0001](0001-administration-easyadmin-dans-l-api.md) (admin) et l'[ADR 0006](0006-identite-du-site-administree-dans-l-api.md) (identité du site). Contrat : section 29 de `boilerplates-commun/SPEC-COMMUNE.md`. L'API Hono reproduit ce contrat.

## Contexte

Un site doit pouvoir publier ses contenus en plusieurs langues, sans que rien ne change pour les sites d'une seule langue : les cinq API clientes de l'agence ont des données réelles en français, et six fronts consomment le contrat actuel. L'interface de l'admin, elle, doit exister en français et en anglais pour tous. Il faut choisir comment stocker les traductions, comment l'API choisit la langue, et comment l'admin les saisit.

## Décision

### Stockage : colonnes actuelles pour la langue par défaut, une colonne JSON `translations` par entité

- Les colonnes texte existantes (`name`, `slug`, `description`, `meta_title`…) gardent la **langue par défaut** du site. Chaque entité traduisible (produit, catégorie, attribut, article, rubrique ou format, page légale, page fixe, identité du site) gagne une colonne JSON `translations` : `{"en": {"name": "…", "slug": "…"}}`, par langue puis par champ. Les textes rangés dans des listes JSON ont une clé composée : `galleryAlt.<empreinte>` (texte de remplacement d'une image de la galerie), `choice.<valeur>` (libellé d'une valeur d'attribut).
- La migration n'ajoute que des colonnes (`translations` à `{}`, `site_identity.locales` à `["fr"]`) : le texte actuel reste celui de la langue par défaut, sans copie ni transformation.
- Le trait `Shared/Domain/Translatable` porte la colonne et la règle : une valeur vide n'est pas gardée (vide = non traduit), et un getter traduit (`getName(?string $locale)`) rend la traduction ou, à défaut, la colonne : **repli champ par champ**. Locale `null` : la langue par défaut, ce qui laisse inchangés les appels existants.
- **Slug par langue** : celui de la langue, ou celui de la langue par défaut s'il n'est pas traduit (`COALESCE(translations -> :locale ->> 'slug', slug)` dans les dépôts Doctrine). L'unicité se vérifie dans les cas d'usage, pour chaque langue de la saisie, sur ce slug effectif (`Shared/Domain/TranslatedSlugs`). L'index unique de la colonne garde la langue par défaut. Les pages légales gardent un slug commun : il est fixé par les routes des fronts.
- **Changer de langue par défaut** échange les textes : les traductions de la nouvelle langue passent dans les colonnes (champ par champ, la valeur actuelle reste pour un champ non traduit) et les colonnes deviennent la traduction de l'ancienne (`Translatable::switchDefaultLocale`). Les cas d'usage `Switch*DefaultLocale` de chaque module le font pour toutes leurs entités ; l'admin les appelle avec la mise à jour de l'identité, dans une seule transaction.

### API : langue demandée, `Content-Language`, `alternates`

- `Shared/Infrastructure/Locale/ContentLocale` choisit la langue par requête : le paramètre `locale` s'il désigne une langue activée, sinon la première langue d'`Accept-Language` activée (poids décroissant, ordre d'écriture à poids égal, région ignorée, poids nul exclu), sinon la langue par défaut. Une valeur non activée est ignorée, comme tout paramètre invalide. Les langues activées viennent de l'identité du site par le port `EnabledLocales`, implémenté par le module `Site`.
- Cette langue est distincte de celle de Symfony, qui reste `en` pour l'API : les messages de validation du contrat ne changent pas.
- `ContentLanguageSubscriber` pose `Content-Language` et `Vary: Accept-Language` sur toute réponse sous `/api/`, documentation exclue. `LocaleOpenApiFactory` documente `locale`, `Accept-Language` et `Content-Language` sur chaque lecture.
- Fiches de produit et d'article, pages légales, nœuds de l'arbre des catégories : `alternates`, `[{locale, slug}]` pour chaque langue activée, langue demandée comprise, `[]` sur un site monolingue. Comme le slug a un repli, chaque élément a un slug dans chaque langue activée.
- `X-Scenario: bilingual` (dev et test, `BilingualScenarioLocales`, même enregistrement que `ScenarioProvider`) ajoute l'anglais aux langues activées pour la requête. Les fixtures restent en `["fr"]` mais portent quelques traductions anglaises, dormantes sans cet en-tête : c'est le cas bilingue des tests et de `make contract`.

### Admin

- **Onglets de langue** : `Form/TranslationFields` ajoute un groupe `translations.<langue>` par langue activée autre que celle par défaut ; `admin/language_tabs.html.twig` range en onglets les champs traduisibles (langue par défaut en premier, seuls ses champs sont obligatoires), à la place du premier d'entre eux. Un site d'une langue n'a ni groupe ni onglet : le formulaire est celui d'avant. Les textes des lignes de la galerie et des valeurs d'attribut sont traduits dans la ligne. Langues du contenu dans l'identité : langue par défaut et autres langues, choisies dans une liste fixe (`ContentLanguages`), chaque langue écrite dans sa propre langue.
- **Interface en français et en anglais** : tous les libellés, aides, menus, titres et messages sont des clés de `translations/admin.{fr,en}.yaml`, les messages de validation propres à l'admin des clés de `validators.{fr,en}.yaml`. Les règles du domaine (`RuleViolation`) gardent leur message français comme modèle à paramètres (`%slug%`) : il sert de clé dans `violations.en.yaml` et de texte en français (repli du traducteur sur `fr`). Le sélecteur de langue d'EasyAdmin envoie `?_locale=` ; `AdminLocaleSubscriber` garde le choix en session, français par défaut, page de connexion comprise.

### Aucune dépendance ajoutée

Tout repose sur Doctrine (colonne JSON), Symfony Form et Symfony Translation, déjà installés.

## Alternatives envisagées

- **Une table de traduction par entité** (`product_translation`…) : huit tables, huit entités et autant de collections Doctrine, des jointures sur chaque lecture, et des textes déjà rangés en JSON (galerie, valeurs d'attribut) qui n'y entrent pas. La migration devrait copier le texte actuel dans la table française. Plus lourd pour un catalogue de quelques dizaines de lignes, sans gain de requête : les traductions se lisent toujours avec l'entité.
- **JSON par champ, toutes langues comprises** (`name` devient `{"fr": …, "en": …}`) : plus de colonne « langue par défaut », donc rien à échanger quand elle change. Mais chaque getter, chaque tri SQL (`ORDER BY name`), chaque colonne de liste d'EasyAdmin et chaque requête par slug changeaient, et la migration réécrivait toutes les données des sites clients. Le modèle retenu laisse le contrat et l'admin d'un site monolingue strictement inchangés, au prix d'un échange des textes au changement de langue par défaut, rare.
- **Bundle de traduction** (KnpLabs DoctrineBehaviors, Gedmo Translatable, a2lix/translation-form-bundle) : tables de traduction ou entrepôt générique, listeners Doctrine qui changent la langue des entités selon la requête, formulaires générés. Plus de code tiers que la solution maison (un trait, un résolveur de langue, un groupe de formulaire), un comportement implicite à la lecture, et une dépendance à suivre pour cinq API clientes. Écarté.
- **Langue de Symfony réglée par `Accept-Language`** (`set_locale_from_accept_language`) : elle traduirait aussi les messages de validation de l'API, qui font partie du contrat.
- **Libellés français comme clés pour toute l'interface** : moins de fichiers, mais le français resterait écrit dans le code. Gardé pour les seules règles du domaine, qui ne connaît pas le traducteur et dont le message sert aussi aux journaux et aux tests unitaires.

## Conséquences

- **Positives** : un site monolingue garde exactement son contrat (comparé réponse par réponse avant et après, `locales` et `alternates` en plus) et son admin ; la migration ne touche aucune donnée ; activer une langue est une case de l'identité ; l'interface de l'admin est entière dans les deux langues, vérifiée par un test qui parcourt toutes les pages.
- **Négatives / coûts** : la recherche par slug traduit passe par une requête SQL native (opérateurs JSON de PostgreSQL), en plus du mapping. L'unicité d'un slug traduit n'a pas d'index : elle repose sur les cas d'usage. L'échange de langue par défaut parcourt toutes les entités, et n'est pas réversible à l'identique pour un champ vide dans l'ancienne langue (un titre de référencement vide prend, au retour, la valeur de la langue qui le remplaçait). Le tri des produits à la une reste celui du nom dans la langue par défaut.
- **Suivi** : l'API Hono reproduit le contrat (langue demandée, `Content-Language`, `alternates`, `X-Scenario: bilingual` et ses traductions de fixtures) ; les fronts (section 29 bis) s'en servent pour leurs routes préfixées, `hreflang` et sélecteur de langue.
