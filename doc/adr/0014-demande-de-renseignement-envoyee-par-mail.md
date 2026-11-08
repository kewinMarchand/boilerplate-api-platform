# 0014 — Vente en ligne débrayable, demande de renseignement envoyée par mail

- **Statut** : Acceptée
- **Décideurs** : Kewin Marchand
- **Complète** : l'[ADR 0006](0006-identite-du-site-administree-dans-l-api.md) (identité du site). Contrat : section 27 de `boilerplates-commun/SPEC-COMMUNE.md`.

## Contexte

Un client peut présenter ses produits sans les vendre en ligne. Les fronts doivent alors retirer prix et panier, et chaque produit propose une demande de renseignement, envoyée par mail par l'API (jamais de `mailto:`). Jusqu'ici, le contact enregistrait le message en base sans l'envoyer à personne.

## Décision

- **Identité du site** : `salesEnabled` (booléen exposé, coché par défaut) et `contactRecipient` (adresse qui reçoit les messages, saisie dans l'admin, jamais exposée), posés ensemble par `SiteIdentity::changeSales`.
- **Contact** : champ facultatif `product` (slug), enregistré avec le message (`contact_message.product_slug`). Le module gagne une couche `Application` : `SendContactMessage` vérifie le produit (`UnknownProduct`, traduit en 422 sur `product` par le processor), enregistre le message, puis l'envoie. Le domaine compose le mail (`ContactMail` : sujet, corps en texte brut, `Reply-To`) et déclare deux ports : `ProductCatalog` (produit par slug) et `ContactMailer` (envoi).
- **Adaptateurs** (`Contact/Infrastructure`) : `CatalogProducts` lit le `ProductRepository` du catalogue ; `SymfonyContactMailer` envoie par Symfony Mailer (`MAILER_DSN`, expéditeur `MAILER_FROM`) au destinataire lu par `GetSiteIdentity`. Un échec d'envoi est journalisé et n'interrompt rien : le message est déjà enregistré, la réponse reste 201. deptrac autorise `ContactInfrastructure` vers `CatalogDomain`, `SiteDomain` et `SiteApplication` : les deux seuls points de contact entre modules, côté adaptateurs, le domaine du contact ne connaît ni le catalogue ni le site.
- **Envoi synchrone**, dans la requête : pas de file de messages (Messenger) pour un formulaire de contact.
- **Dev** : service Mailpit dans `compose.yaml` (interface sur `127.0.0.1:8026`, SMTP sur le réseau du compose seulement). **Tests** : `null://null` forcé par `when@test` dans `mailer.yaml` (le conteneur de dev pose `MAILER_DSN` vers Mailpit, et une variable réelle l'emporte sur `.env.test`) ; les mails sont lus par les assertions du `MessageLoggerListener`, l'échec simulé par `tests/Support/FailingMailer`, qui décore le mailer.

## Alternatives envisagées

- **Destinataire en variable d'environnement** : un changement d'adresse demanderait un déploiement ; la spec le veut dans la fiche de l'admin.
- **Contrôle du produit par une contrainte de validation** sur la ressource : la violation sortirait avec les autres champs, mais le cas d'usage relirait le produit pour son nom. Une seule lecture, dans le cas d'usage ; un produit inconnu n'arrive que si le front envoie un slug périmé.
- **Envoi asynchrone (Messenger)** : un transport et un worker de plus, pour un volume de quelques messages ; le message reste de toute façon enregistré si l'envoi échoue.
- **Lire le produit en SQL depuis le contact** : éviterait la dépendance au domaine du catalogue, au prix d'une requête qui double le mapping du produit.

## Conséquences

- **Positives** : les messages arrivent enfin chez le client ; le visiteur n'a jamais à renvoyer un message déjà enregistré ; aucun mail réel ne part en dev ni en test.
- **Négatives / coûts** : deux dépendances (`symfony/mailer`, `egulias/email-validator`, environ 0,8 Mo) ; un conteneur de plus en dev ; la requête attend la réponse du serveur SMTP. Un message dont l'envoi a échoué n'est pas renvoyé automatiquement, et l'admin n'a pas encore de liste des messages pour le retrouver.
- **Suivi** : l'API Hono applique le même contrat (Mailpit sur 8027).
