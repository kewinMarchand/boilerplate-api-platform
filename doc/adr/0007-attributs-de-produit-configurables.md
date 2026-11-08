# 0007 — Attributs de produit configurables, définis dans l'admin

- **Statut** : Acceptée
- **Décideurs** : Kewin Marchand
- **Complète** : [0005](0005-collection-de-produits-et-facettes-a-part.md) (filtres et facettes), [0001](0001-administration-easyadmin-dans-l-api.md) (section de l'admin)

## Contexte

Les caractéristiques d'un produit sont figées dans le code : l'exposition et la taille, deux énumérations. Un vrai client en a d'autres, qui lui sont propres : diamètre, entraxe et déport d'une jante, kilométrage et énergie d'un véhicule, durée d'une expérience. Les ajouter une à une demanderait une migration, un champ d'admin, un paramètre de filtre et un changement de contrat à chaque client. La section 23 de `SPEC-COMMUNE.md` décrit le contrat attendu des deux API et des fronts.

## Décision

- **Attributs définis dans l'admin** (section « Attributs ») : code (fixé à la création), libellé, type `choice`, `number` ou `text`, unité facultative, filtre du catalogue (type `choice` seulement), position, et pour une liste ses valeurs ordonnées (code et libellé). Ils s'ajoutent à l'exposition et à la taille, qui ne changent pas : le contrat existant ne perd rien.
- **Stockage en JSON** : les valeurs d'une liste dans une colonne `choices` de `product_attribute`, les valeurs d'un produit dans une colonne `attribute_values` de `product`, par code. Les règles d'intégrité (valeur connue, valeur retirée ou type changé alors que des produits les portent, suppression qui nettoie les produits) sont dans le modèle et les cas d'usage, pas dans des clés étrangères.
- **Contrat** : `attributes` sur `Product` et `ProductDetail` (attributs renseignés, par position, valeur en chaîne et son libellé), `attributes` dans `ProductFacets` (attributs filtrables portés dans la branche, compteurs disjonctifs comme l'exposition), filtre `attributes[<code>][]` sur la liste et les facettes, codes et valeurs inconnus ignorés.
- **Recherche en PHP**, comme pour les autres facettes : `ProductSearch` reçoit les définitions et applique les filtres d'attributs avec les autres.

## Alternatives envisagées

- **Tables relationnelles** (`attribute_choice`, `product_attribute_value` avec clés étrangères) : l'intégrité est garantie par la base, mais trois tables, des collections Doctrine dans le domaine (qui n'en a aucune aujourd'hui) et des jointures pour lire 24 produits. Le refus « valeur utilisée par N produits » reste de toute façon à écrire pour avoir un message en français.
- **Une colonne par attribut**, ajoutée par migration : chaque client redemande du code, ce que l'admin doit justement éviter.
- **Valeurs en texte libre sans définition** : pas de facette possible, pas de libellé, pas de contrôle de saisie.

## Conséquences

- Un client ajoute ses caractéristiques sans livraison de code. Les fronts les affichent sur la fiche et en font des facettes sans connaître leurs codes à l'avance.
- La base ne protège pas les valeurs des produits : seule l'écriture par les cas d'usage les garde cohérentes. Les fixtures passent par `ProductAttribute` et `Product::changeAttributeValues`.
- Les comptes « portés par N produits » parcourent tous les produits, comme la recherche. À revoir avec elle si le catalogue grossit (un index JSONB ou des tables relationnelles).
- Les fronts lisent un tableau générique (`code`, `label`, `type`, `value`, `valueLabel`, `unit`) : le typage précis d'un attribut n'apparaît pas dans l'OpenAPI.

## Suivi

Rouvrir si un attribut doit porter plusieurs valeurs par produit, si un type de plus est demandé (date, booléen), ou si le catalogue grossit au point que les comptes en PHP coûtent.
