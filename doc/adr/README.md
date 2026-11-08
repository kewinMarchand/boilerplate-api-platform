# Décisions d'architecture

Une décision structurante par fichier, numérotée, jamais réécrite une fois acceptée : une décision qui change est remplacée par une nouvelle ADR.

| ADR                                                     | Décision                                                         | Statut   |
| ------------------------------------------------------- | ---------------------------------------------------------------- | -------- |
| [0001](0001-administration-easyadmin-dans-l-api.md)     | Administration du catalogue et des contenus par EasyAdmin, dans l'API | Acceptée |
| [0002](0002-images-envoyees-par-l-admin.md)             | Images envoyées par l'admin : volume de l'API, variantes générées à l'envoi | Acceptée, complétée par 0003, modifiée par 0009 |
| [0003](0003-variante-intermediaire-des-produits.md)     | Variante intermédiaire de 640 px pour les images des produits    | Acceptée, modifiée par 0009 |
| [0004](0004-carrousel-de-produits-a-la-une.md)          | Carrousel de l'accueil : produits à la une, diapositives retirées | Proposée |
| [0005](0005-collection-de-produits-et-facettes-a-part.md) | Liste des produits en collection standard, facettes dans une ressource à part | Acceptée |
| [0006](0006-identite-du-site-administree-dans-l-api.md)  | Identité du site administrée dans l'API, couleurs de marque calculées par l'API | Acceptée, modifiée par 0008, complétée par 0011 et 0012 |
| [0007](0007-attributs-de-produit-configurables.md)    | Attributs de produit configurables, définis dans l'admin, stockés en JSON | Acceptée |
| [0008](0008-couleur-de-marque-sombre-a-4-5.md)        | Couleur de marque du thème sombre éclaircie jusqu'à 4,5:1         | Acceptée |
| [0009](0009-galerie-et-sources-plus-petites-des-produits.md) | Galerie des produits en JSON, sources de 400 px acceptées, jamais agrandies | Acceptée |
| [0010](0010-rubriques-formats-et-audio-des-articles.md) | Rubriques et formats d'articles dans une table, audio lu en PHP sans dépendance | Acceptée |
| [0011](0011-metadonnees-administrables.md)            | Métadonnées administrables : pages fixes, produits, articles, image de partage | Acceptée |
| [0012](0012-favicons-administrables.md)               | Favicons générés en PNG depuis l'icône du site                    | Acceptée |
