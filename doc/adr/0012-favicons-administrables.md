# 0012 — Favicons générés en PNG depuis l'icône du site

- **Statut** : Acceptée
- **Décideurs** : Kewin Marchand
- **Complète** : l'[ADR 0006](0006-identite-du-site-administree-dans-l-api.md) (un champ de plus dans l'identité du site) et l'[ADR 0002](0002-images-envoyees-par-l-admin.md) (un usage d'image encodé en PNG). Contrat : puce « Icône du site » de la section 22 de `boilerplates-commun/SPEC-COMMUNE.md`.

## Contexte

Les favicons (onglet, écran d'accueil iOS, manifeste) sont des fichiers livrés par chaque front (section 8). Habiller un front pour un client demande encore un développeur pour eux, alors que le nom, le logo et les couleurs s'administrent déjà.

## Décision

- **Usage `icon`** : image carrée, 512 px au moins, recadrée au centre, jamais agrandie, variantes de **32, 180, 192 et 512 px en PNG seulement** (`ImageUsage::formats()`, `ImageFormat::Png`). Le PNG est le seul format que lisent tous les navigateurs pour `<link rel="icon">` et les écrans d'accueil ; l'AVIF et le WebP n'y servent pas. GD écrit le PNG (compression 9) en gardant la transparence. Pas de SVG ni d'ICO (refusés par la liste des types acceptés de `StoreImage`).
- **Identité du site** : colonne `icon_key`, image décorative (aucun texte de remplacement), case « Retirer l'icône », aperçu de la variante 192 dans l'admin.
- **Contrat** : `icon` vaut `null` ou `{ png32, png180, png192, png512 }` (adresses absolues, immuables), construit par `SiteIcon`.

## Alternatives envisagées

- **Objet image du contrat** (`sources`, `srcset`) : inadapté, chaque favicon a une balise et une taille à lui.
- **Fichier ICO multi-tailles** : GD ne l'écrit pas, et les navigateurs actuels prennent le PNG.
- **Favicons générés par les fronts depuis le logo** : un logo n'est pas carré, et la règle serait recopiée dans six fronts.

## Conséquences

- **Positives** : l'icône se change depuis l'admin, sans déploiement des fronts.
- **Négatives / coûts** : un PNG de 512 px pèse plus qu'un WebP ; les fronts doivent faire passer l'origine de l'API dans `img-src` de leur CSP et lire les icônes du manifeste depuis l'API.
- **Suivi** : Hono suit le même contrat.
