# 0013 — Contraste de la couleur de marque sombre mesuré sur la surface secondaire

- **Statut** : Acceptée
- **Décideurs** : Kewin Marchand
- **Modifie** : l'[ADR 0008](0008-couleur-de-marque-sombre-a-4-5.md), sur la seule couleur contre laquelle le seuil est mesuré. Contrat : section 22 de `boilerplates-commun/SPEC-COMMUNE.md`.

## Contexte

L'ADR 0008 éclaircit la couleur de marque sombre jusqu'à 4,5:1 sur le fond `#0f141d`. Les fronts posent aussi cette couleur sur des surfaces plus claires que le fond. En préprod, le rouge `#e10600` devient `#e84440` (4,68:1 sur le fond), qui ne fait que 4,4:1 sur une surface sombre de `#1a1a1d` : axe signale un contraste insuffisant.

## Décision

Le seuil de 4,5:1 se mesure sur `#1c222e`, la surface secondaire sombre de boilerplates-ui, plus claire que le fond (`BrandPalette::DARK_CONTRAST_SURFACE`). Le reste ne change pas : pas de 5 % de blanc, `-dark` à 25 % de blanc en plus, surfaces mélangées avec le fond `#0f141d`, `on-*` à `#0f141d` (au-dessus de 4,5:1 par construction, le fond étant plus sombre que la surface).

## Alternatives envisagées

- **Relever le seuil sur le fond (5:1 ou plus)** : un nombre sans lien avec les surfaces réelles, qui ne garantit rien si la bibliothèque éclaircit ses surfaces.
- **Mesurer sur la surface la plus claire de la bibliothèque** : éclaircit davantage la couleur pour des cas où le texte de marque ne se pose pas.

## Conséquences

- **Positives** : la couleur de marque reste à 4,5:1 au moins sur le fond et sur les surfaces sombres jusqu'à `#1c222e` (`#e10600` devient `#ec5d59`, 4,76:1 sur `#1c222e`, 5,19:1 sur `#1a1a1d`).
- **Négatives / coûts** : la couleur sombre s'éloigne un peu plus de la couleur saisie (`#1d4ed8` passe de 25 à 35 % de blanc, `#6c8ce6`) ; les jetons sombres d'une identité déjà saisie changent à la prochaine lecture, sans migration ; les fronts qui comparent des valeurs sombres en dur les mettent à jour.
- **Suivi** : l'API Hono applique la même règle pour rester sur le même contrat.
