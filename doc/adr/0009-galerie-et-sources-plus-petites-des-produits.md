# 0009 — Galerie des produits et sources plus petites, jamais agrandies

- **Statut** : Acceptée
- **Décideurs** : Kewin Marchand
- **Modifie** : l'[ADR 0002](0002-images-envoyees-par-l-admin.md) et l'[ADR 0003](0003-variante-intermediaire-des-produits.md), sur la largeur minimale des images de produit et les largeurs générées. Contrat : section 24 de `boilerplates-commun/SPEC-COMMUNE.md`.

## Contexte

Une fiche produit n'a qu'une image. Les clients en veulent plusieurs (détail, mise en situation), et leurs photos de catalogue font souvent 500 ou 600 px : l'ADR 0002, avec la largeur minimale de 800 px de l'ADR 0003, les refuse. Agrandir une photo pour atteindre 800 px ne crée aucun détail et alourdit le fichier.

## Décision

- **Sources plus petites** : l'usage `product` accepte une source de 400 px de large une fois recadrée au carré (`ImageUsage::minimumWidth()`, la plus grande variante pour les autres usages). Les variantes s'arrêtent à la largeur de la source (`ImageUsage::widthsUpTo()`) : celles de l'usage plus étroites que la source, puis la plus grande ramenée à la largeur de la source. 600 px donne 400 et 600 ; 700 px donne 400, 640 et 700 ; 800 px et plus, 400, 640 et 800 comme avant. Même règle, écrite une fois, pour tous les usages : ceux dont la largeur minimale est la plus grande variante ne changent pas.
- **Largeur gardée** : `Image::$width` vaut la largeur de la plus grande variante quand elle est plus petite que celle de l'usage, `null` sinon. Colonne `product.image_width` nullable : les lignes existantes et les sources assez larges gardent `null`, les empreintes ne changent pas. `width` et `height` du contrat sont ceux de la plus grande variante.
- **Galerie** : jusqu'à 8 images de plus que l'image principale, ordonnées, dans une colonne JSON `product.gallery` (`key`, `alt`, `width`), comme les valeurs d'attributs (ADR 0007). `Product::changeGallery()` refuse la 9ᵉ ; `ProductGallery` (couche `Application`) écarte les lignes retirées et les lignes d'ajout vides, compte avant tout encodage, puis trie par position. Texte de remplacement vide : le nom du produit. `ProductDetail` gagne `gallery` ; la liste et le carrousel ne changent pas.
- **Admin** : une ligne par image enregistrée (aperçu et texte de remplacement, position, remplacement du fichier, case « Retirer cette image ») et deux lignes d'ajout, sans JavaScript, comme les valeurs d'attributs.

## Alternatives envisagées

- **Table `product_image`** avec clé étrangère : l'ordre et la suppression en cascade viennent de la base, mais une collection Doctrine entre dans le domaine, qui n'en a aucune. Les images n'ont aucune vie propre hors de leur produit.
- **Agrandir les sources étroites jusqu'à 800 px** : contrat inchangé, mais des fichiers plus lourds sans aucun détail de plus.
- **Garder 800 px minimum** : la cause du changement.

## Conséquences

- **Positives** : un client envoie ses photos telles qu'il les a ; une fiche montre jusqu'à neuf images ; le contrat reste le même objet image, seul `srcset` raccourcit.
- **Négatives / coûts** : une image de 400 px s'affiche moins nette en grand sur un écran dense ; le formulaire produit s'allonge. La base ne protège pas la galerie (JSON) : seules les écritures par les cas d'usage la gardent cohérente. Les fronts doivent lire `width` au lieu de supposer 800.
- **Suivi** : Hono suit le même contrat ; rouvrir si un client demande des recadrages choisis ou plus de 8 images.
