<?php

declare(strict_types=1);

namespace App\Content\Infrastructure\Doctrine\Fixtures;

use App\Content\Domain\MetaPage;
use App\Content\Domain\PageMeta;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Une ligne par page fixe, vide (les fronts gardent leurs valeurs), sauf la description du blog, qui exerce le contrat
 * (SPEC-COMMUNE, section 26).
 */
final class PageMetaFixtures extends Fixture
{
    public const string BLOG_DESCRIPTION = 'Conseils et actualités du modèle : rendu serveur, accessibilité, composants sans dépendance.';
    /** Dormante tant que le site n'active que le français (ADR 0015). */
    public const string BLOG_DESCRIPTION_EN = 'Tips and news from the template: server rendering, accessibility, dependency-free components.';

    public function load(ObjectManager $manager): void
    {
        foreach (MetaPage::cases() as $page) {
            $meta = new PageMeta($page);
            if (MetaPage::Blog === $page) {
                $meta->update('', self::BLOG_DESCRIPTION);
                $meta->changeTranslations(['en' => ['description' => self::BLOG_DESCRIPTION_EN]]);
            }
            $manager->persist($meta);
        }
        $manager->flush();
    }
}
