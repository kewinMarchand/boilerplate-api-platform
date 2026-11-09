<?php

declare(strict_types=1);

namespace App\Blog\Infrastructure\Doctrine\Fixtures;

use App\Blog\Domain\Article;
use App\Blog\Domain\ArticleTerm;
use App\Blog\Domain\ArticleTermKind;
use App\Media\Domain\ImageUsage;
use App\Media\Infrastructure\Fixtures\DemoAudio;
use App\Media\Infrastructure\Fixtures\DemoImages;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Trois articles, deux rubriques et un format affectés (ADR 0010), un fichier audio de démonstration avec son intervenant.
 */
final class ArticleFixtures extends Fixture
{
    public function __construct(
        private readonly DemoImages $images,
        private readonly DemoAudio $audio,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $serverRendering = new Article(
            'pourquoi-rendre-le-contenu-cote-serveur',
            'Pourquoi rendre le contenu côté serveur',
            'Le contenu éditorial arrive dans le HTML initial : meilleur référencement et LCP.',
            new \DateTimeImmutable('2026-09-15'),
            $this->images->import('blog-1', ImageUsage::Article, '', decorative: true),
            <<<'MD'
                Une page dont le texte n’arrive qu’après l’exécution du JavaScript reste vide pour un moteur de recherche pressé, et pour le visiteur dont le téléphone est lent. Rendre le contenu sur le serveur, c’est envoyer une page déjà lisible.

                ## Ce que le visiteur gagne

                Le premier affichage contient le titre, le texte et les images. Le navigateur peut commencer à peindre la page sans attendre le moindre script, et l’image la plus grande, celle que mesure le *Largest Contentful Paint*, est découverte dès la lecture du HTML.

                - le texte est lisible même si un script échoue ;
                - les liens fonctionnent avant l’hydratation ;
                - le référencement voit la même page que le visiteur.

                ## Ce qu’il reste au navigateur

                Le JavaScript n’ajoute que ce qui demande une interaction : un carrousel qu’on fait glisser, des filtres appliqués sans recharger la page. Sans lui, la page reste utilisable : c’est l’amélioration progressive.
                MD,
        );
        $carousel = new Article(
            'un-carrousel-accessible-sans-dependance',
            'Un carrousel accessible sans dépendance',
            'Défilement natif, boutons explicites, pas de lecture automatique.',
            new \DateTimeImmutable('2026-09-22'),
            $this->images->import('blog-2', ImageUsage::Article, '', decorative: true),
            <<<'MD'
                Un carrousel est souvent le composant le moins accessible d’une page d’accueil : défilement automatique impossible à arrêter, boutons sans nom, diapositives annoncées dans le désordre. Il peut pourtant rester simple.

                ## Partir du défilement natif

                Sans JavaScript, la piste défile au doigt, à la molette ou au clavier grâce à `overflow-x: auto` et à l’accrochage `scroll-snap`. Le script ne fait qu’ajouter le glisser à la souris et les boutons.

                ## Nommer chaque élément

                - les boutons Précédent et Suivant disent ce qu’ils font, et se désactivent aux extrémités ;
                - chaque diapositive est un groupe annoncé « 2 sur 5 » ;
                - les points de pagination indiquent la diapositive courante.

                Enfin, pas de lecture automatique : c’est le visiteur qui décide du rythme, et l’animation se coupe quand il demande moins de mouvement.
                MD,
        );
        $enhancedMode = new Article(
            'le-mode-accessibilite-renforcee',
            'Le mode accessibilité renforcée',
            'Texte agrandi, espacements WCAG 1.4.12 et contraste AAA, sans logique dupliquée.',
            new \DateTimeImmutable('2026-10-01'),
            $this->images->import('blog-3', ImageUsage::Article, '', decorative: true),
            <<<'MD'
                Le mode accessibilité renforcée agrandit le texte, élargit les espacements et pousse les contrastes au niveau AAA, d’un seul clic, sans recharger la page.

                ## Un seul thème, des jetons redéfinis

                Aucun second thème n’est maintenu à côté du premier. Le bouton pose un attribut sur l’élément racine, et les jetons de la charte (couleurs, tailles du texte, épaisseur du focus) prennent leurs valeurs renforcées sous cet attribut. Les composants ne changent pas.

                ## Ce que le mode applique

                - les espacements du critère WCAG 1.4.12 sur le texte de contenu ;
                - des couleurs qui atteignent un contraste de 7:1 ;
                - un indicateur de focus plus épais.

                Le choix est gardé dans le navigateur et appliqué avant le premier affichage : la page ne clignote pas d’un mode à l’autre.
                MD,
        );
        $advice = new ArticleTerm(ArticleTermKind::Section, 'conseils', 'Conseils', 1);
        $news = new ArticleTerm(ArticleTermKind::Section, 'actualites', 'Actualités', 2);
        $guide = new ArticleTerm(ArticleTermKind::Format, 'guide', 'Guide', 1);
        $serverRendering->classify($advice, $guide);
        $carousel->classify($advice, null);
        $carousel->changeAudio($this->audio->import('article-demo'), 'Présenté par Hina Teriierooiterai');
        $enhancedMode->classify($news, null);
        // Traductions anglaises dormantes (ADR 0015) : le texte de l'article reste en repli sur le français
        $enhancedMode->changeTranslations(['en' => [
            'slug' => 'enhanced-accessibility-mode',
            'title' => 'The enhanced accessibility mode',
            'excerpt' => 'Larger text, WCAG 1.4.12 spacing and AAA contrast, without duplicated logic.',
        ]]);
        $advice->changeTranslations(['en' => ['name' => 'Tips', 'slug' => 'tips']]);
        foreach ([$advice, $news, $guide, $serverRendering, $carousel, $enhancedMode] as $entity) {
            $manager->persist($entity);
        }
        $manager->flush();
    }
}
