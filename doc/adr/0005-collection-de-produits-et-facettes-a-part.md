# 0005 — Liste des produits en collection standard, facettes dans une ressource à part

- **Statut** : Acceptée
- **Décideurs** : Kewin Marchand
- **Remplace** : la note « Catalogue » du `CLAUDE.md` (liste servie par une opération `Get` qui renvoie un objet `ProductList`), qui n'avait pas d'ADR.

## Contexte

Les autres listes de l'API suivent la convention d'API Platform : la ressource porte le nom de l'élément (`Article`, `Task`, `Category`) et sa liste est une `GetCollection`. En JSON simple, c'est un tableau ; en JSON-LD, une `Collection` Hydra dont chaque élément a son IRI.

La liste des produits fait exception. `GET /api/products` est une opération `Get` qui renvoie un objet `ProductList` (`items`, `totalItems`, `page`, `itemsPerPage`, `totalPages`, `facets`), dont les éléments sont un second type, `ProductItem`. La raison : en JSON simple, une `GetCollection` sérialise un tableau, qui ne peut porter ni le total ni les compteurs de facettes. Le prix de cette exception :

- deux noms (`ProductList`, `ProductItem`) pour une seule notion, et un troisième pour le carrousel (`FeaturedProduct.ProductItem`) ;
- une pagination maison (`items` au lieu de `member`) ;
- en JSON-LD, des produits sans IRI (`/.well-known/genid/…`) ;
- une forme que l'API Hono doit recopier, puisque les deux API ont le même contrat.

## Décision

- **`Product`** est la ressource des produits de liste. Elle a deux `GetCollection` : `/products` (une page filtrée et triée) et `/featured-products` (le carrousel de l'accueil). En JSON simple, chacune renvoie un tableau de `Product`. En JSON-LD, la liste est une `Collection` Hydra : le provider renvoie un `TraversablePaginator`, qui donne le total filtré et les liens de pages (`view`), et chaque produit a son IRI `/api/products/{slug}`.
- **`ProductFacets`** est une ressource à part : `GET /api/product-facets`, mêmes filtres que la liste, sans tri ni pagination. Elle renvoie `totalItems` et les compteurs disjonctifs (`exposure`, `size`, `category`). Le total y vit parce qu'un tableau JSON ne peut pas le porter : c'est le résumé de l'ensemble filtré.
- `ProductDetail` (`GET /api/products/{slug}`) ne change pas.
- Les fronts lisent la liste et les facettes en parallèle, et calculent le nombre de pages à partir du total.

## Alternatives envisagées

- **Garder `ProductList`** : rien à migrer, mais l'exception reste, avec ses trois noms et ses produits sans IRI.
- **Compteurs ajoutés à la collection par un normaliseur** : une seule requête côté front, mais un normaliseur sur mesure, qui ne joue qu'en JSON-LD (le JSON simple reste un tableau) et que l'OpenAPI ne décrit pas sans travail de plus.
- **Fronts en JSON-LD pour la liste** : le total et la pagination viennent d'Hydra, mais les facettes restent à placer, et le contrat de l'API Hono, en JSON simple, devrait apprendre le JSON-LD.
- **Pagination dans des en-têtes HTTP** (`X-Total-Count`, `Link`) : rien de standard dans API Platform, et l'OpenAPI ne les type pas pour les fronts.

## Conséquences

- La liste des produits suit la même convention que les autres collections, et un seul nom, `Product`, désigne un produit de liste.
- La page du catalogue fait deux requêtes au lieu d'une (liste et facettes). Elles partent en parallèle, et chacune refait la recherche en PHP : avec 24 produits, le coût est nul. À revoir si le catalogue devient gros.
- Les filtres sont déclarés deux fois, dans les attributs des deux ressources : un attribut PHP n'accepte pas d'appel de fonction. Ils sont lus par une seule classe, `ProductQuery`.
- Changement cassant : les deux API, la spec commune, les six fronts et les sites de l'agence changent ensemble.

## Suivi

Rouvrir si le catalogue grossit au point qu'une recherche coûte (index de recherche, facettes calculées par la base), ou si un front a besoin des compteurs sans la liste, ou l'inverse, dans une même requête.
