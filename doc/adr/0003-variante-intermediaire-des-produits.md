# 0003 — Variante intermédiaire de 640 px pour les images des produits

- **Statut** : Acceptée, modifiée par l'[ADR 0009](0009-galerie-et-sources-plus-petites-des-produits.md) (largeur minimale à l'envoi : 400 px au lieu de 800)
- **Date** : 2026-11-07
- **Décideurs** : Kewin Marchand
- **Complète** : l'[ADR 0002](0002-images-envoyees-par-l-admin.md), dont elle ne change que les largeurs des produits.

## Contexte

L'ADR 0002 génère les images des produits en 400 et 800 px. Le front Nuxt servait jusque-là une variante intermédiaire de 640 px, ajoutée après mesure : la carte produit occupe presque toute la largeur sur mobile, et Lighthouse simule un écran d'environ 412 px à une densité de 1,75, soit un besoin d'environ 620 px. Sans 640, le navigateur prend la variante 800, presque deux fois plus lourde.

Depuis le passage aux images de l'API, la CI de Nuxt mesure 0,88 et 0,89 en performance sur `/catalogue` et `/catalogue/plantes-interieur`, pour un seuil de 0,90, à deux reprises. Les autres fronts passent, mais le même écart de poids s'applique à tous : c'est l'image LCP de la page catalogue.

## Décision

Les images des produits ont trois variantes : **400, 640 et 800 px**, en AVIF et en WebP, dans les deux API. Le reste de l'ADR 0002 ne change pas : `width` et `height` restent ceux de la plus grande variante, `src` reste la plus petite variante WebP, et la largeur minimale à l'envoi reste 800 px.

## Alternatives envisagées

- **Abaisser le seuil de Lighthouse des pages catalogue de Nuxt à 0,85** : rapide, mais affaiblit un contrôle pour une cause identifiée, qui pèse aussi sur les autres fronts.
- **Laisser chaque front générer ses variantes** : contraire à l'ADR 0002, qui retire toute photo de contenu des fronts.
- **Une variante intermédiaire pour tous les usages** : les diapositives (640, 1280) et les articles (480, 960) n'ont pas de mesure qui la réclame.

## Conséquences

- **Positives** : le mobile reçoit une image à sa taille sur le catalogue de chaque front, sans changement de code dans les fronts (le `srcset` vient de l'API).
- **Négatives / coûts** : deux fichiers de plus par produit et un encodage de plus à l'envoi. Les images déjà stockées n'ont pas de variante 640 : `StoreImage` ré-encode une image dès qu'une variante manque, mais l'original n'est pas gardé (ADR 0002). En démonstration, `make db-reset` réimporte tout ; un volume réel demanderait une commande qui dérive la 640 de la variante 800.
- **Suivi** : à rouvrir si une mesure réclame une variante intermédiaire pour les diapositives ou les articles.
