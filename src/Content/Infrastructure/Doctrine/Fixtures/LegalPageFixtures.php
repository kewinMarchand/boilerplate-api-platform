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

            L'ensemble des contenus de ce site (textes, images, code) est la propriété de Exemple SAS (société fictive), sauf mention contraire. Toute reproduction sans autorisation écrite préalable est interdite.
            MD));
        $manager->persist(new LegalPage('donnees-personnelles', 'Données personnelles', <<<'MD'
            ## Responsable de traitement

            Exemple SAS (société fictive), 1 rue de l’Exemple, 13000 Marseille.

            ## Finalité et base légale

            Le formulaire de contact collecte votre nom, votre adresse e-mail et votre message, dans le seul but de vous répondre. Ce traitement repose sur votre consentement, exprimé par l'envoi du formulaire.

            ## Durée de conservation

            Les messages sont conservés trois ans à compter du dernier échange, puis supprimés.

            ## Vos droits

            Vous disposez sur vos données des droits suivants :

            - accès ;
            - rectification ;
            - effacement ;
            - opposition.

            Pour les exercer, écrivez à [contact@example.com](mailto:contact@example.com). Si la réponse ne vous satisfait pas, vous pouvez adresser une réclamation à la [Commission nationale de l'informatique et des libertés (CNIL)](https://www.cnil.fr/fr/plaintes).

            ## Cookies et stockage local

            Ce site ne dépose aucun cookie de mesure d'audience. Seule votre préférence pour le mode accessibilité renforcée est enregistrée dans votre navigateur, sans jamais nous être transmise.
            MD));
        $manager->persist(new LegalPage('accessibilite', 'Déclaration d\'accessibilité', <<<'MD'
            Exemple SAS (société fictive) s'engage à rendre ce site accessible conformément à l'article 47 de la loi n° 2005-102 du 11 février 2005. Cette déclaration s'applique à ce site.

            ## État de conformité

            Ce site est **non conforme** avec le référentiel général d'amélioration de l'accessibilité (RGAA), version 4.1.2.

            Aucun audit n'a encore été réalisé.

            ## Résultats des tests

            Tests automatisés axe-core uniquement (règles WCAG 2.1 A et AA), sur chaque page, sur ordinateur et mobile, dans les deux modes d'affichage. Ils ne couvrent qu'une partie des critères du RGAA et ne valent pas audit.

            ## Contenus non accessibles

            À compléter après audit.

            ## Mode accessibilité renforcée

            Le bouton « Accessibilité renforcée » de l'en-tête agrandit le texte, augmente les espacements et le contraste, souligne les liens, épaissit l'indicateur de focus et coupe les animations. Le choix est mémorisé dans votre navigateur.

            ## Environnement de test

            - Chromium (Playwright), affichage ordinateur et mobile ;
            - axe-core, via @axe-core/playwright ;
            - Lighthouse CI.

            ## Retour d'information et contact

            Si vous ne parvenez pas à accéder à un contenu ou à un service, écrivez à [contact@example.com](mailto:contact@example.com) pour être orienté vers une alternative accessible ou obtenir le contenu sous une autre forme.

            ## Voies de recours

            Si vous avez signalé un défaut d'accessibilité sans obtenir de réponse satisfaisante, vous pouvez saisir le [Défenseur des droits](https://formulaire.defenseurdesdroits.fr/) par le formulaire en ligne, contacter un délégué dans votre région ou écrire, sans affranchissement, à : Défenseur des droits, Libre réponse 71120, 75342 Paris CEDEX 07.
            MD));
        $manager->flush();
    }
}
