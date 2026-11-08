# 0011 — Métadonnées administrables : pages fixes, produits, articles, image de partage

- **Statut** : Acceptée
- **Décideurs** : Kewin Marchand
- **Complète** : l'[ADR 0006](0006-identite-du-site-administree-dans-l-api.md) (un champ de plus dans l'identité du site) et l'[ADR 0001](0001-administration-easyadmin-dans-l-api.md) (section « Référencement »). Contrat : section 26 de `boilerplates-commun/SPEC-COMMUNE.md`.

## Contexte

Le titre, la description et l'image de partage de chaque page sont calculés par les fronts (section 10). Un client qui veut retoucher son référencement doit passer par un développeur. La spec rend ces valeurs saisissables, toutes facultatives : vides, les fronts gardent les leurs.

## Décision

- **Pages fixes** (module `Content`) : `PageMeta`, une ligne par clé de `MetaPage` (`home`, `catalog`, `blog`, `contact`, `sitemap`, `legal-notice`, `privacy`, `accessibility`), titre de 60 caractères et description de 160 au plus. Les lignes existent toujours : la migration les insère vides, les fixtures les recréent (seule la description de `blog` est saisie). L'admin les modifie sans en créer ni en supprimer, comme les pages légales. `GET /api/page-metas` sert toutes les clés dans l'ordre de `MetaPage`, chaînes vides pour une ligne absente.
- **Produits et articles** : `metaTitle` et `metaDescription` dans leur formulaire (`Admin/Form/MetaFields`, commun aux trois formulaires), exposés sur `ProductDetail` et `ArticleDetail` ; ni la liste ni le carrousel ne changent.
- **Image de partage** : usage d'image `share` (1200 × 630, recadré, une variante 1200 en AVIF et WebP), dans l'identité du site (`shareImage`, texte de remplacement, le nom du site par défaut, case « Retirer l'image de partage »).
- Longueurs contrôlées à la frontière (formulaires) et par les colonnes ; les valeurs sont enregistrées sans blancs de début ni de fin.

## Alternatives envisagées

- **Lignes des pages fixes créées à la première modification** (comme l'identité du site) : la liste de l'admin serait vide sur une base neuve, et EasyAdmin n'édite que des lignes existantes. La liste des clés est écrite deux fois (énumération et migration), mais la migration est un instantané qui ne bouge plus.
- **Métadonnées des pages fixes dans l'identité du site** : seize champs dans une fiche déjà longue.
- **Clé `page` comme identifiant** : EasyAdmin attend un identifiant scalaire simple, une énumération en clé primaire complique ses routes. Identifiant entier, `page` unique.

## Conséquences

- **Positives** : le référencement se règle sans livraison ; un champ vide ne change rien au rendu actuel des fronts.
- **Négatives / coûts** : une page fixe ajoutée aux fronts demande un cas de `MetaPage` et une migration qui insère sa ligne.
- **Suivi** : Hono suit le même contrat.
