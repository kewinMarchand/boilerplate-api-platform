# 0004 — Carrousel de l'accueil : produits à la une, diapositives retirées

- **Statut** : Proposée
- **Date** : 2026-11-07
- **Décideurs** : Kewin Marchand
- **Modifie** : l'[ADR 0001](0001-administration-easyadmin-dans-l-api.md) (section « Carrousel » de l'admin retirée) et l'[ADR 0002](0002-images-envoyees-par-l-admin.md) (usage « diapositive » retiré).

## Contexte

Le carrousel de l'accueil des fronts affichait cinq diapositives éditoriales (titre, texte, photo 16:9), administrées à part dans l'admin et servies par `GET /api/slides`. Elles ne menaient nulle part : le carrousel n'était pas cliquable, et ses sujets (Heliconia, Anthurium…) recoupaient le catalogue sans y renvoyer.

Une fiche produit existe désormais (`GET /api/products/{slug}`, route `/produit/<slug>` des fronts). Le besoin exprimé : un carrousel qui montre des produits et mène à leur fiche.

## Décision

- Le carrousel de l'accueil montre les **produits « À la une »** : une case de l'admin des produits (`Product::featured`), servis par `GET /api/featured-products` (éléments de la liste du catalogue, par nom). Chaque diapositive est liée à la fiche de son produit.
- Les **diapositives sont retirées** : entité, admin, `GET /api/slides`, table (migration `Version20261107210000`), fixtures et photos `slide-*`, usage d'image `slide` (640 et 1280 px).
- Le retrait vient après la bascule des six fronts et des sites de l'agence vers le carrousel de produits, pour qu'aucun site déployé ne perde son accueil entre deux déploiements.

## Alternatives envisagées

- **Lien saisi sur chaque diapositive** (champ « lien » libre) : souple, mais garde deux contenus à administrer pour le même sujet, et un lien libre peut casser sans que rien ne le signale.
- **Diapositive liée à un produit choisi dans l'admin** : garde les textes éditoriaux, au prix d'une relation de plus et d'un contenu en double avec la fiche.
- **Lien vers la catégorie de même slug** : aucune saisie, mais fragile (« nenuphar » ne correspond à aucune catégorie) et cassé au premier renommage.

## Conséquences

- Un seul contenu à tenir : la fiche produit. Le carrousel suit le catalogue (prix, stock, photo) sans double saisie.
- Les textes éditoriaux du carrousel disparaissent : il n'affiche plus que le nom et le prix. Un accroche éditoriale demanderait un champ de plus sur le produit.
- L'ordre du carrousel est alphabétique, sans réglage : une position demanderait un champ de plus.
- Les photos du carrousel sont carrées (photos des produits), et non plus en 16:9.
- Une base existante perd ses diapositives à la migration, et aucun produit n'y est à la une tant que l'admin n'en coche pas : la section disparaît de l'accueil jusque-là.

## Suivi

À rouvrir si le carrousel doit porter des contenus qui ne sont pas des produits (promotion, actualité).
