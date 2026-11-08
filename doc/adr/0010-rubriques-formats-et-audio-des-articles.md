# 0010 — Rubriques et formats des articles dans une table, audio lu en PHP sans dépendance

- **Statut** : Acceptée
- **Décideurs** : Kewin Marchand
- **Complète** : l'[ADR 0001](0001-administration-easyadmin-dans-l-api.md) (deux sections de plus dans l'admin) et l'[ADR 0002](0002-images-envoyees-par-l-admin.md) (un fichier audio stocké et servi comme les images). Contrat : section 25 de `boilerplates-commun/SPEC-COMMUNE.md`.

## Contexte

Les articles n'ont ni classement ni média autre qu'une image. La spec demande deux listes administrables (rubriques et formats, `{ slug, name, position }`), un filtre par rubrique et par format, et un fichier audio (MP3, M4A ou OGG, 100 Mo au plus) dont la durée est lue à l'envoi, avec un intervenant. L'image Docker n'a ni ffmpeg ni ffprobe, et aucune bibliothèque PHP de lecture audio n'est installée.

## Décision

- **Une entité, deux listes** : `ArticleTerm` (table `article_term`) porte une sorte, `ArticleTermKind::Section` ou `Format`, un slug unique dans sa sorte (contrainte `kind, slug`), un nom et une position. Les deux listes ont exactement les mêmes champs et les mêmes règles : un seul jeu de cas d'usage (`Create`/`Update`/`DeleteArticleTerm`, la sorte en paramètre), une seule ressource (`ArticleTerm`, deux opérations `/article-sections` et `/article-formats`, la sorte dans `extraProperties`), un contrôleur d'admin abstrait et deux sections. `ArticleTermKind` porte les messages accordés (« La rubrique X est utilisée… », « Le format X est utilisé… »).
- **Article** : `section` et `format` (clés étrangères nullables vers `article_term`), `Article::classify()` refuse un terme de l'autre liste. Suppression d'un terme porté par un article refusée, compte fait en PHP sur la liste des articles (comme les attributs, ADR 0007). Filtres `section` et `format` de `GET /api/articles` appliqués en PHP par le provider ; un slug que ne porte aucun article donne une liste vide.
- **Audio** (module `Media`) : `Audio` (empreinte, `AudioFormat`, durée en secondes arrondie, 1 au moins), `StoreAudio` (100 Mo, format reconnu au contenu, empreinte `sha256("audio\n" + contenu)` tronquée, fichier stocké tel quel sous `<empreinte>.<extension>`), port `AudioInspector`. Le fichier passe par son **chemin** (fichier temporaire de la requête, `MediaStorage::putFile`), jamais en mémoire : 100 Mo ne tiennent pas dans la limite de mémoire de PHP. Servi par Caddy sous `/media/` comme les images (`Content-Type` par extension, requêtes partielles pour l'avance rapide). Colonnes `audio_key`, `audio_format`, `audio_duration`, `speaker` (80 caractères).
- **Durée lue en PHP seul** (`Media/Infrastructure/Audio/HeaderAudioInspector`), sans dépendance : MP3 (tag ID3v2 sauté, deux en-têtes de trame Layer III consécutifs exigés, nombre de trames de l'en-tête Xing, Info ou VBRI, sinon débit constant), OGG Vorbis ou Opus (position de la dernière page, fréquence de l'en-tête d'identification, pré-saut d'Opus), M4A (boîte `mvhd`, au moins une piste son et aucune piste vidéo : une vidéo MP4 est refusée). Durées vérifiées contre ffprobe sur les fichiers de `tests/Fixtures/audio`.
- **Limite d'envoi** : `docker/uploads.ini` passe à 100 Mo (`post_max_size` 112 Mo). Le fichier est copié dans l'image : il faut reconstruire l'image (`make build`) puis relancer le conteneur pour qu'elle s'applique.
- **Fixtures** : « Conseils », « Actualités » et « Guide », un fichier de démonstration généré par ffmpeg (bip de 4 secondes, sans droits, `fixtures/audio/article-demo.mp3`) avec « Présenté par Hina Teriierooiterai ».

## Alternatives envisagées

- **Deux entités `ArticleSection` et `ArticleFormat`** : vocabulaire plus direct, mais deux fois les cas d'usage, dépôts, mappings, ressources et contrôleurs, pour des listes identiques.
- **ffprobe dans l'image Docker** : lit tout format, mais ajoute ffmpeg et ses bibliothèques à l'image pour lire trois en-têtes.
- **`james-heinrich/getid3`** : bibliothèque PHP éprouvée, mais une dépendance de plus dont l'API n'utiliserait qu'une fonction, et qui lit bien plus de formats que ceux acceptés.
- **Durée saisie à la main** : sujette à l'erreur, contraire à la spec.

## Conséquences

- **Positives** : un client classe ses articles et publie un podcast sans développeur ; aucune dépendance ni outil système de plus.
- **Négatives / coûts** : la lecture d'en-têtes est du code à maintenir ; un MP3 à débit variable sans en-tête Xing ni VBRI aurait une durée approchée (débit de la première trame). Un fichier audio remplacé reste dans le volume, comme les images (ADR 0002).
- **Suivi** : rouvrir si un autre format est demandé (FLAC, WAV) ou si l'image embarque ffmpeg pour une autre raison.
