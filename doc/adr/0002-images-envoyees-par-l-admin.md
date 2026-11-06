# 0002 — Images envoyées par l'admin : volume de l'API, variantes générées à l'envoi

- **Statut** : Acceptée
- **Date** : 2026-11-06
- **Décideurs** : Kewin Marchand
- **Révisée le 06/11/2026** (en place, par exception : aucun code ne reposait encore sur elle) : les diapositives ont deux largeurs, 640 et 1280, et non trois. Les photos de démonstration des diapositives font 1280 px et le contrôle de largeur minimale les aurait refusées ; seul le hero existe en 1920, et il reste dans les fronts. La décision ne change pas.
- **Complète** : l'[ADR 0001](0001-administration-easyadmin-dans-l-api.md), qui laisse ouvert le « stockage des images envoyées par l'admin », et le même point de l'[ADR 0001 de boilerplate-hono](https://github.com/kewinMarchand/boilerplate-hono/blob/main/doc/adr/0001-administration-ecrite-en-hono.md).

## Contexte

L'admin choisit aujourd'hui un **numéro de visuel** : de 1 à 5 pour une diapositive, de 1 à 12 pour un produit. Le contrat public expose cet entier (`image`), et chaque front construit le chemin d'un fichier livré dans ses propres assets (`/images/slide-3-640.avif`), copié depuis `boilerplates-commun/shared-assets`. Un client ne peut donc pas changer une photo sans développeur ni déploiement du front, et un front qui oublie de livrer ses assets affiche des images cassées.

Forces en présence :

- un projet client = une API, sa base, son admin (ADR 0001) : pas de service de plus sans raison forte ;
- les fronts servent des variantes AVIF puis WebP générées à l'avance, à des largeurs fixes : c'est ce qui tient Lighthouse au-dessus de 0,9. Une image encodée à la première requête fait chuter la mesure (constaté avec l'optimiseur de Next) ;
- le contrat est partagé avec Hono et vérifié par `make contract` : le modifier oblige à modifier les deux API et les six fronts ;
- l'accessibilité exige un texte de remplacement juste (RGAA 1.1 à 1.3), que seul celui qui choisit la photo peut écrire ;
- les photos de démonstration ont leurs métadonnées EXIF et GPS retirées : une photo envoyée par un client doit l'être aussi.

## Décision

**On stocke les images dans un volume persistant de l'API, et l'API les sert elle-même. Les variantes sont générées une fois, à l'envoi.**

- **Stockage** : un port `MediaStorage` (domaine partagé), avec un adaptateur fichiers sur un volume Docker monté dans l'API (`var/media/`). L'API sert ces fichiers sous `/media/`, en `Cache-Control: public, max-age=31536000, immutable`. Le volume est sauvegardé avec la base : les deux forment les données du projet.
- **Variantes à l'envoi** : AVIF et WebP, aux largeurs de chaque usage, recadrées au ratio de l'usage : diapositive 16:9 (640, 1280), article 16:9 (480, 960), produit 1:1 (400, 800). Nom des fichiers : `<empreinte du contenu>-<largeur>.<format>`. L'empreinte change avec le fichier : aucun cache à purger. L'original n'est pas gardé.
- **Contrôles à l'envoi** : JPEG, PNG, WebP ou AVIF seulement, type vérifié sur le contenu et non sur l'extension ; 10 Mo au plus ; largeur au moins égale à la plus grande variante de l'usage ; métadonnées (EXIF, GPS, profil) retirées.
- **Contrat** : `image` devient un objet, identique dans les deux API : `{ "alt": string, "width": int, "height": int, "sources": [{ "type": "image/avif" | "image/webp", "srcset": string }], "src": string }`. `src` est la variante WebP la plus petite, adresse absolue. Les fronts ne construisent plus aucun chemin : ils posent `sources` dans un `<picture>`.
- **Texte de remplacement** : saisi dans l'admin avec l'image, obligatoire sauf pour une image décorative (case « décorative », `alt` vide). Un produit propose son nom par défaut.
- **Fixtures** : `make db-reset` importe les photos de démonstration de `shared-assets` par le même chemin que l'admin (contrôles, variantes, stockage). Les e2e et Lighthouse voient les mêmes images qu'aujourd'hui.
- **Hono** : même port, même disposition du volume, mêmes largeurs, même contrat. L'outil d'encodage est propre à chaque API (libvips des deux côtés si possible : extension `vips` en PHP, `sharp` en Node), choisi au premier lot après vérification du support AVIF dans l'image Docker.
- **Hors périmètre** : le hero de l'accueil reste un visuel du front, il n'est pas une ressource de l'API.

## Alternatives envisagées

- **Stockage objet (S3, MinIO)** : utile à plusieurs serveurs ou à un CDN. Ajoute un service, ses identifiants et sa sauvegarde à chaque projet, pour des sites servis par une seule instance. Écarté pour l'instant ; le port `MediaStorage` permet de le brancher sans toucher au domaine.
- **Images en base (`bytea`, BSON)** : un seul élément à sauvegarder, mais la base grossit, les sauvegardes s'alourdissent et chaque image servie passe par PHP ou Node. Écarté.
- **Redimensionnement à la volée** (à la première requête, puis en cache) : moins de fichiers, mais la première visite de chaque taille est lente, et Lighthouse, qui ne passe qu'une fois, la mesure. Déjà constaté avec l'optimiseur de Next. Écarté.
- **Service d'images tiers (Cloudinary, imgix)** : coût par projet, dépendance à un tiers et données hors de l'Union européenne pour certains. Écarté.
- **Garder les visuels dans les fronts** : la situation actuelle, celle que cette ADR corrige.

## Conséquences

- **Positives** :
  - le client change ses photos et leur texte de remplacement sans développeur ;
  - les fronts ne livrent plus de photos de contenu, seulement leur identité (logo, favicons, hero) : plus d'images cassées faute d'assets copiés ;
  - les performances restent celles des variantes statiques.
- **Négatives / coûts** :
  - **rupture du contrat** : `image` passe d'un entier à un objet. Les deux API, `make contract` et les six fronts changent ensemble, dans l'ordre API puis fronts ;
  - une extension native d'encodage AVIF dans chaque image Docker, et un encodage AVIF lent à l'envoi (quelques secondes par image) ;
  - un volume de plus à sauvegarder et à restaurer avec la base, à documenter dans chaque projet ;
  - la CSP des fronts doit autoriser l'origine de l'API dans `img-src` ;
  - une image remplacée laisse ses anciennes variantes : un nettoyage des fichiers sans référence est à prévoir (commande de console).
- **Suivi** : rouvrir la décision si un projet tourne sur plusieurs instances ou derrière un CDN (stockage objet), ou si un usage demande des recadrages choisis par le client plutôt qu'un recadrage centré.
