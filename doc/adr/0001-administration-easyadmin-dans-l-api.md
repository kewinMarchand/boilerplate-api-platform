# 0001 — Administration du catalogue et des contenus par EasyAdmin, dans l'API

- **Statut** : Acceptée
- **Introduite par** : commit `46df658`
- **Décideurs** : Kewin Marchand
- **Point « Hono » remplacé** le 04/11/2026 par l'[ADR 0001 de boilerplate-hono](https://github.com/kewinMarchand/boilerplate-hono/blob/main/doc/adr/0001-administration-ecrite-en-hono.md) : Hono gagne une admin écrite en Hono. Le reste de cette décision ne change pas.
- **Révisée le 02/11/2026** (réécrite en place, par exception : les outils sont encore en création et aucun code ne reposait sur la première version) : le chemin d'écriture est précisé (l'admin appelle les cas d'usage, qui enregistrent par le dépôt Doctrine) et le slug est unique dans tout le catalogue, et non dans sa branche. La décision ne change pas.
- **Révise** : `boilerplates-commun/ARCHITECTURE-DDD.md` §2.2, §4.4.2 et §5 (« le catalogue n'a aucune écriture ») et le rôle décrit dans `CLAUDE.md` (« pas d'authentification, pas d'écriture hors contact »), une fois acceptée.

## Contexte

Les cinq fronts lisent leurs données dans cette API (ou dans `boilerplate-hono`, au contrat identique), choisie par `API_URL`. L'API n'expose que des lectures, plus `POST /api/contact_messages`. Le catalogue (catégories, produits), les articles du blog et les tâches viennent des fixtures Doctrine (`src/*/Infrastructure/Doctrine/Fixtures/`). Le carrousel de l'accueil et les pages légales sont écrits dans les vues des fronts.

Pour un projet client, toute modification du catalogue ou d'un texte passe donc par un développeur : on modifie le code, on commite, on recharge les fixtures, et le rechargement vide la base. Le client ne peut rien changer lui-même.

Forces en présence :

- un projet client part d'un template : une API, sa base PostgreSQL et un front ;
- la stack native de l'API est Symfony, l'équipe la maîtrise ;
- le contrat public (OpenAPI) est partagé avec Hono et vérifié par `make contract`. Le modifier oblige à modifier les deux API ;
- certains clients ont déjà un ERP qui fait foi pour les articles, les prix et les stocks. La plupart n'en ont pas.

## Décision

On rend le catalogue et les contenus administrables par **EasyAdmin, dans l'application Symfony de l'API**. On ne crée ni service ni base séparés.

- **Un projet client = une API, une base PostgreSQL, une admin.** L'admin est servie par la même application, sous `/admin`, derrière une authentification Symfony Security (comptes d'administration en base, mot de passe haché).
- **Périmètre administré** : catégories et produits ; articles du blog ; diapositives du carrousel et pages légales, qui deviennent des ressources de l'API en lecture, lues par les fronts comme le reste.
- **Le contrat public ne gagne aucune écriture.** L'admin appelle les cas d'usage de la couche application (`CreateProduct`, `UpdateCategory`…), qui vérifient les règles puis enregistrent par le dépôt Doctrine. Elle n'écrit jamais par des opérations API Platform. Les fronts et `make contract` ne voient que les nouvelles ressources en lecture, ajoutées aussi dans Hono.
- **Le catalogue gagne des écritures, donc des agrégats.** `Category` et `Product` protègent leurs invariants à la modification : slug unique dans **tout** le catalogue (les URL publiques désignent une catégorie par son slug seul, `?category=jardin`), parent existant, profondeur de 3 niveaux au plus, prix positif ou nul, visuel compris entre 1 et 12, et pas de suppression d'une catégorie qui a encore des produits ou des sous-catégories. Les règles propres à un objet sont dans l'agrégat ; celles qui consultent les autres objets (unicité, suppression) sont vérifiées par le cas d'usage à l'aide des ports.
- **EasyAdmin sert les listes et les fiches.** Pour créer et modifier, ses formulaires portent sur des objets de saisie, pas sur les entités : les entités n'ont pas de setters.
- **Fixtures** : elles restent la source des données de développement et de test (e2e, `X-Scenario`). En production, les données arrivent par l'admin, ou par un import initial (CSV, export d'ERP). Le schéma n'évolue plus que par migrations Doctrine.
- **ERP** : aucun par défaut. Quand un client en a un, il fait foi pour les données qu'il possède (références, prix, stocks), et une synchronisation les importe dans l'API. L'admin garde ce que l'ERP n'a pas : photos, descriptions, SEO. Cette synchronisation fera l'objet d'une ADR propre au projet concerné.
- **Hono** : il reste une API de contrat, sans admin. Ses données restent des fixtures.

## Alternatives envisagées

- **API Platform Admin (React-admin)** : générée depuis l'OpenAPI, rapide à brancher. Elle impose d'ouvrir des opérations d'écriture dans le contrat public, donc de les recoder dans Hono, et ajoute une application React à construire et déployer. Rejetée.
- **CMS headless (Strapi, Directus, Payload)** : riche pour composer des pages libres. Mais cela ajoute un second serveur et une seconde base à côté de l'API, avec une synchronisation ou un double branchement des fronts. Les besoins actuels (catalogue, articles, quelques pages fixes) ne le justifient pas.
- **ERP comme back-office (Dolibarr, Odoo)** : il gère des articles, mais pas les contenus du site (textes, images, SEO). Il impose aussi un outil de gestion à des clients qui n'en ont pas besoin. Il est réservé au rôle de source synchronisée.
- **Admin dans chaque front** : à écrire cinq fois, dans cinq stacks, avec une authentification par front. Rejetée.
- **Rester sur les fixtures** : convient à la démonstration, pas à un client qui doit modifier son site sans développeur.
- **CRUD standard d'EasyAdmin, directement sur les entités** : moins de code (EasyAdmin fournit formulaires et enregistrement). Mais les entités doivent exposer des setters, les règles qui consultent la base deviennent des contraintes de validation Symfony hors du domaine, et un futur import ou une synchronisation devrait recopier ces règles ou passer par le validateur. Rejetée : un boilerplate sert de modèle d'architecture aux projets clients, et l'import initial comme la synchronisation ERP sont prévus.

## Conséquences

- **Positives** :
  - le client met à jour son catalogue et ses textes sans développeur ni déploiement ;
  - un seul déploiement, une seule base, une seule authentification par projet ;
  - les fronts ne changent pas, hormis la lecture des nouvelles ressources (carrousel, pages légales) ;
  - le contrat public reste en lecture, donc la parité Hono reste simple.
- **Négatives / coûts** :
  - dépendance `easycorp/easyadmin-bundle`, et Symfony Security à configurer (comptes, protection CSRF, limitation des tentatives de connexion) ;
  - le domaine du catalogue se complexifie : agrégats, invariants, cas d'usage d'écriture et leurs tests ; dans l'admin, un objet de saisie et un formulaire par opération, à côté des listes d'EasyAdmin. Le modèle « sans agrégat » de ARCHITECTURE-DDD est à réviser ;
  - stockage des images envoyées par l'admin : volume persistant ou stockage objet, avec sauvegarde. À choisir avant la première mise en production ;
  - les données de production ne se rechargent plus : migrations obligatoires et sauvegardes de la base ;
  - Hono n'a pas d'admin : un projet qui part de Hono n'est pas administrable ;
  - les nouvelles ressources de lecture (carrousel, pages légales) sont à ajouter aux deux API et aux cinq fronts.
- **Suivi** : rouvrir la décision si un client doit composer librement des pages (un CMS deviendrait plus simple), si l'admin doit être utilisable par des profils aux droits différents au-delà de ce qu'EasyAdmin offre, ou si un ERP doit écrire en continu dans l'API (une API d'écriture authentifiée deviendrait alors nécessaire).