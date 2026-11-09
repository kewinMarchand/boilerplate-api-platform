<?php

declare(strict_types=1);

namespace App\Site\Infrastructure\Doctrine\Fixtures;

use App\Site\Domain\SiteIdentity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Valeurs du modèle, une seule langue (fr). L'accroche anglaise ne sert qu'avec X-Scenario: bilingual (ADR 0015).
 */
final class SiteIdentityFixtures extends Fixture
{
    public const string TAGLINE_EN = 'Website template with boilerplates-ui, equipped for QA and end-to-end testing.';

    public function load(ObjectManager $manager): void
    {
        $identity = SiteIdentity::byDefault();
        $identity->changeTranslations(['en' => ['tagline' => self::TAGLINE_EN]]);
        $manager->persist($identity);
        $manager->flush();
    }
}
