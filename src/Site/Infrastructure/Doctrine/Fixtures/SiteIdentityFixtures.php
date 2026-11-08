<?php

declare(strict_types=1);

namespace App\Site\Infrastructure\Doctrine\Fixtures;

use App\Site\Domain\SiteIdentity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class SiteIdentityFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $manager->persist(SiteIdentity::byDefault());
        $manager->flush();
    }
}
