# 0008 — Couleur de marque du thème sombre éclaircie jusqu'à 4,5:1

- **Statut** : Acceptée
- **Décideurs** : Kewin Marchand
- **Modifie** : l'[ADR 0006](0006-identite-du-site-administree-dans-l-api.md), sur le seul seuil de contraste du thème sombre. Contrat : section 22 de `boilerplates-commun/SPEC-COMMUNE.md`.

## Contexte

L'ADR 0006 éclaircit la couleur de marque, en sombre, par pas de 5 % de blanc jusqu'à 7:1 sur le fond `#0f141d`, comme les jetons sombres de boilerplates-ui. À 7:1, une couleur saturée perd sa teinte : le bleu `#1d4ed8` devient `#839eea` (45 % de blanc), un pastel que le client ne reconnaît plus comme sa couleur. Le seuil de 7:1 est celui du niveau AAA, que le mode renforcé garantit déjà avec les couleurs de la bibliothèque (section 4), jamais avec celles du client. Décision de Kewin : « baisse le contraste à 4,5 ».

## Décision

En sombre, la couleur de marque est éclaircie par pas de 5 % de blanc jusqu'à **4,5:1** au moins sur `#0f141d` (minimum AA du texte courant), au lieu de 7:1. Le reste de l'ADR 0006 ne change pas : `-dark` à 25 % de blanc en plus, `on-*` de la couleur du fond (4,5:1 par construction, le contraste étant symétrique), surfaces à 8, 12 et 19 %. `BrandPalette::DARK_CONTRAST` porte le seuil.

## Alternatives envisagées

- **Garder 7:1** : la couleur sombre s'éloigne trop de la couleur saisie, ce qui motive le changement.
- **Seuil intermédiaire (6:1)** : sans référence normative, il ne se justifie ni auprès d'un client ni dans un audit RGAA.
- **Seuil choisi dans l'admin** : un réglage de plus que le client n'a pas les moyens d'arbitrer. Écarté, comme la saisie des jetons dans l'ADR 0006.

## Conséquences

- **Positives** : la couleur sombre reste proche de la couleur saisie (`#1d4ed8` devient `#567ae2`, 25 % de blanc) ; le texte courant reste conforme au niveau AA.
- **Négatives / coûts** : les jetons sombres d'une identité déjà saisie changent à la prochaine lecture, sans migration (ils sont recalculés) ; les fronts dont les tests comparent des valeurs sombres en dur les mettent à jour. Le niveau AAA en sombre n'est plus tenu hors du mode renforcé.
- **Suivi** : l'API Hono applique le même seuil pour rester sur le même contrat.
