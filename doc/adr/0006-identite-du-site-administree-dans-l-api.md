# 0006 — Identité du site administrée dans l'API, couleurs de marque calculées par l'API

- **Statut** : Acceptée, modifiée par l'[ADR 0008](0008-couleur-de-marque-sombre-a-4-5.md) (couleur sombre éclaircie jusqu'à 4,5:1 au lieu de 7:1), complétée par l'[ADR 0011](0011-metadonnees-administrables.md) (image de partage) et l'[ADR 0012](0012-favicons-administrables.md) (favicons)
- **Décideurs** : Kewin Marchand
- **Complète** : l'[ADR 0001](0001-administration-easyadmin-dans-l-api.md) (une section de plus dans l'admin) et l'[ADR 0002](0002-images-envoyees-par-l-admin.md) (un usage d'image sans recadrage). Contrat : section 22 de `boilerplates-commun/SPEC-COMMUNE.md`.

## Contexte

Le nom, l'accroche, le logo et les couleurs d'un site vivent aujourd'hui dans le code de chaque front (`site.ts` ou équivalent, jetons de boilerplates-ui). Habiller un front pour un client demande donc un développeur, un fork de configuration et un déploiement, alors que le reste du contenu (catalogue, articles, pages légales) s'administre déjà dans l'API. Décision de Kewin : « l'habillage client doit se faire depuis l'admin ».

Forces en présence :

- six fronts et deux API partagent un contrat : une règle de couleur dupliquée dans chaque front divergerait vite ;
- la couleur principale sert de couleur de lien : un client qui choisit une couleur trop claire casse le contraste AA de tout le site ;
- les fronts ont un mode sombre et un mode renforcé (sections 4 et 20) : une seule couleur saisie ne suffit pas pour les deux thèmes ;
- un logo n'a pas de ratio fixe et porte souvent de la transparence, à l'inverse des photos de l'ADR 0002.

## Décision

**L'identité du site est une ressource de l'API (`GET /api/site-identity`), saisie dans une fiche de l'admin ; quand des couleurs de marque sont saisies, l'API calcule les douze jetons, en clair et en sombre.**

- **Module `Site`** : agrégat `SiteIdentity`, une seule ligne (identifiant fixé à 1). Sans ligne enregistrée, `GetSiteIdentity` sert les valeurs du modèle (`SiteIdentity::byDefault()`, aussi utilisées par les fixtures et par `X-Scenario: empty`) : l'API répond sur une base neuve sans donnée insérée par la migration.
- **Couleurs facultatives** : la principale et la secondaire, les deux ou aucune (`BrandColors::fromInput`). Sans couleurs, `theme` vaut `null` et les fronts gardent les jetons de boilerplates-ui : c'est l'état des fixtures et des valeurs par défaut, les modèles ne changent donc pas.
- **Clair** (fond `#ffffff`) : règles des chartes des maquettes (`maquettes-sites/chartes.py`, `declinaisons`) : couleur saisie, `-dark` à 25 % de noir, `on-*` blanc ou `#111827` selon le meilleur contraste, surfaces à 8, 18 et 32 %. Arrondi au pair, comme Python, pour donner les mêmes valeurs que les maquettes.
- **Sombre** (fond `#0f141d`, celui de boilerplates-ui) : couleur éclaircie par pas de 5 % de blanc jusqu'à 7:1 sur le fond, `-dark` à 25 % de blanc en plus, `on-*` de la couleur du fond (4,5:1 garanti par construction), surfaces à 8, 12 et 19 %.
- **Règles** (`BrandColors`, objet valeur du domaine) : principale à 4,5:1 au moins sur `#ffffff` (couleur de lien), secondaire qui porte un texte lisible (`on-secondary` à 4,5:1 au moins), une seule couleur refusée. Les refus (`SiteRuleViolation`) s'affichent tels quels dans l'admin, ratios tronqués à deux décimales pour ne jamais afficher « 4,50 » sous le seuil.
- **Logo** : usage `ImageUsage::Logo`, largeurs 200 et 400, sans recadrage. `ImageUsage::ratio()` vaut `null` pour cet usage ; la hauteur de la plus grande variante dépend du fichier et est gardée par `Image::$height` (colonne `logo_height`). GD garde la transparence (PNG, WebP, AVIF). Le SVG reste refusé (contenu actif) : PHP 8.5 lit ses dimensions, c'est la liste des types acceptés de `StoreImage` qui l'écarte. Texte de remplacement : le nom du site par défaut, pas de case « décorative ». Case « Retirer le logo » (un fichier envoyé en même temps est ignoré). `UpdateSiteIdentity` vérifie les couleurs avant d'encoder le logo : une saisie refusée ne laisse aucun fichier.
- **Admin** : section « Identité du site », dont la page (GET et POST) est le formulaire, appelé par `UpdateSiteIdentity` ; création, suppression, liste et fiche désactivées.

## Alternatives envisagées

- **Couleurs obligatoires, initialisées aux couleurs de la bibliothèque** : les jetons de boilerplates-ui viennent d'échelles réglées à la main, qu'aucune règle ne reproduit ; les modèles auraient changé de rendu. Écarté au profit de `theme: null`.
- **Fronts qui calculent les déclinaisons** : la règle serait recopiée dans six fronts et deux API, en TypeScript, PHP et Twig. Écarté : les fronts ne posent que des variables CSS.
- **Saisir les douze jetons (ou vingt-quatre) dans l'admin** : plus libre, mais un client n'a ni les règles de contraste ni le temps de les appliquer. Écarté.
- **Ligne insérée par la migration** : la ressource existerait toujours, mais les valeurs par défaut seraient écrites deux fois (SQL et fixtures). Écarté au profit du repli de `GetSiteIdentity`.
- **Logo recadré à un ratio fixe** (comme les produits) : déforme ou rogne la plupart des logos. Écarté.
- **Logo SVG** : net à toutes les tailles, mais un SVG peut porter du script. Écarté, comme dans la spec.

## Conséquences

- **Positives** :
  - un même front sert plusieurs clients, habillés depuis l'admin, sans fork ni déploiement ;
  - la règle de couleur vit à un seul endroit, testée contre les valeurs de `chartes.py` ;
  - une couleur principale illisible est refusée avant d'atteindre un front.
- **Négatives / coûts** :
  - saisies dans l'admin, les couleurs de boilerplates-ui ne redonnent pas exactement les jetons de la bibliothèque (surfaces claires, `-dark`, couleurs sombres un peu moins claires) : seul `theme: null` garantit le rendu de la bibliothèque ;
  - Hono et les six fronts doivent suivre le contrat, dans l'ordre API puis fronts.
- **Suivi** : rouvrir si la règle des couleurs change dans les maquettes, ou si un client demande des jetons saisis à la main.
