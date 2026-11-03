<?php

declare(strict_types=1);

namespace App\Content\Infrastructure\Doctrine\Fixtures;

use App\Content\Domain\LegalPage;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Textes d'exemple, avec un éditeur fictif. La section sur la mesure d'audience reste dans les fronts :
 * elle dépend de leur configuration.
 */
final class LegalPageFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $manager->persist(new LegalPage('mentions-legales', 'Mentions légales', <<<'MD'
            Les informations ci-dessous sont des valeurs d'exemple, fictives, à remplacer depuis l'administration.

            ## Éditeur

            Exemple SAS (société fictive), 1 rue de l’Exemple, 13000 Marseille. SIRET : 000 000 000 00000.

            Contact : [contact@example.com](mailto:contact@example.com)

            ## Directeur de la publication

            Camille Exemple

            ## Hébergeur

            Hébergeur Exemple, 2 avenue de l’Exemple, 75000 Paris.

            ## Propriété intellectuelle

            L'ensemble des contenus de ce site (textes, images, code) est la propriété de Exemple SAS (société fictive), sauf mention contraire. Toute reproduction ou représentation, totale ou partielle, sans autorisation écrite préalable est interdite.
            MD));
        $manager->persist(new LegalPage('donnees-personnelles', 'Données personnelles', <<<'MD'
            ## Responsable de traitement

            Exemple SAS (société fictive), 1 rue de l’Exemple, 13000 Marseille, représentée par Camille Exemple. Contact : [contact@example.com](mailto:contact@example.com).

            ## Finalité

            Le formulaire de contact collecte votre nom, votre adresse e-mail et votre message, dans le seul but de vous répondre. Ces données ne sont ni cédées ni utilisées à des fins commerciales.

            ## Base légale

            Le traitement repose sur l'intérêt légitime de l'éditeur à répondre aux demandes qui lui sont adressées (article 6.1.f du RGPD).

            ## Durée de conservation

            Les messages sont conservés trois ans au plus à compter du dernier échange, puis supprimés.

            ## Vos droits

            Vous disposez sur vos données des droits suivants :

            - accès ;
            - rectification ;
            - effacement ;
            - opposition ;
            - limitation du traitement.

            Pour les exercer, écrivez à [contact@example.com](mailto:contact@example.com). Nous répondons dans un délai d'un mois.

            ## Réclamation

            Si vous estimez que vos droits ne sont pas respectés, vous pouvez adresser une réclamation à la [Commission nationale de l'informatique et des libertés (CNIL)](https://www.cnil.fr/fr/plaintes).

            ## Cookies et stockage local

            Ce site ne dépose aucun cookie de mesure d'audience ni de publicité. Vos préférences d'affichage (mode accessibilité renforcée, thème clair ou sombre) sont enregistrées dans votre navigateur, sans jamais nous être transmises, jusqu'à ce que vous les changiez. Un cookie technique de session, exempté de consentement, peut être posé après l'envoi du formulaire de contact sans JavaScript, pour afficher la confirmation.
            MD));
        $manager->persist(new LegalPage('accessibilite', 'Déclaration d\'accessibilité', <<<'MD'
            Exemple SAS (société fictive) s'engage à rendre ce site accessible conformément à l'article 47 de la loi n° 2005-102 du 11 février 2005. Cette déclaration s'applique à ce site.

            ## État de conformité

            Ce site est **non conforme** avec le référentiel général d'amélioration de l'accessibilité (RGAA), version 4.1.2.

            Aucun audit n'a encore été réalisé.

            ## Résultats des tests

            Seuls des tests automatisés ont été réalisés, avec axe-core (règles WCAG 2.1 niveaux A et AA), sur toutes les pages, en affichage ordinateur et mobile, en mode standard et en mode accessibilité renforcée. Ces tests ne valent pas audit : ils ne couvrent qu'une partie des critères du RGAA.

            ## Contenus non accessibles

            À compléter après audit.

            ## Mode accessibilité renforcée

            Le bouton « Accessibilité renforcée » de l'en-tête agrandit le texte, augmente les espacements et le contraste, souligne les liens, épaissit l'indicateur de focus et coupe les animations. Le choix est mémorisé dans votre navigateur.

            ## Établissement de cette déclaration

            Date : à compléter après audit.

            Technologies utilisées : HTML, CSS, JavaScript.

            ## Environnement de test

            - Chromium piloté par Playwright, affichage ordinateur (1280 × 720) ;
            - Chromium piloté par Playwright, émulation mobile (Pixel 7) ;
            - outils : axe-core (via @axe-core/playwright) et Lighthouse CI.

            Pages testées :

            - [Accueil](/) ;
            - [Tâches](/taches) ;
            - [Contact](/contact) ;
            - [Catalogue](/catalogue) ;
            - [Mentions légales](/mentions-legales) ;
            - [Données personnelles](/donnees-personnelles) ;
            - [Déclaration d'accessibilité](/accessibilite) ;
            - [Plan du site](/plan-du-site).

            ## Retour d'information et contact

            Si vous ne parvenez pas à accéder à un contenu ou à un service, écrivez à [contact@example.com](mailto:contact@example.com) ou passez par le [formulaire de contact](/contact) pour être orienté vers une alternative accessible ou obtenir le contenu sous une autre forme.

            ## Voies de recours

            Si vous avez signalé un défaut d'accessibilité qui vous empêche d'accéder à un contenu ou à un service sans obtenir de réponse satisfaisante, vous pouvez :

            - écrire un message au [Défenseur des droits](https://formulaire.defenseurdesdroits.fr/) ;
            - contacter le délégué du Défenseur des droits dans votre région ;
            - envoyer un courrier par la poste, sans affranchissement : Défenseur des droits, Libre réponse 71120, 75342 Paris CEDEX 07.
            MD));
        $manager->flush();
    }
}
