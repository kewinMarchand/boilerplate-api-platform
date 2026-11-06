<?php

declare(strict_types=1);

namespace App\Content\Infrastructure\Doctrine\Fixtures;

use App\Content\Domain\Slide;
use App\Media\Domain\ImageUsage;
use App\Media\Infrastructure\Fixtures\DemoImages;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class SlideFixtures extends Fixture
{
    public function __construct(private readonly DemoImages $images)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $slides = [
            ['heliconia', 'Heliconia', 'Des bractées rouges et jaunes en pinces de homard, qui tiennent des semaines au jardin.', 'Bractées rouges et jaunes d’un heliconia, en gros plan'],
            ['anthurium', 'Anthurium', 'Une spathe vernissée qui fleurit presque toute l’année à la lumière vive d’un intérieur.', 'Spathe rouge et brillante d’un anthurium'],
            ['calliandra', 'Calliandra', 'Des pompons d’étamines rouges qui attirent colibris et papillons en lisière de jardin.', 'Pompon d’étamines rouges d’un calliandra'],
            ['strelitzia', 'Strelitzia', 'L’oiseau de paradis, sa fleur orange et bleue et ses grandes feuilles en éventail.', 'Fleur orange et bleue d’un strelitzia'],
            ['nenuphar', 'Nénuphar', 'Des fleurs qui s’ouvrent au matin à la surface du bassin et se referment le soir.', 'Fleur de nénuphar ouverte sur l’eau d’un bassin'],
        ];
        foreach ($slides as $index => [$slug, $title, $text, $alt]) {
            $image = $this->images->import('slide-'.($index + 1), ImageUsage::Slide, $alt);
            $manager->persist(new Slide($slug, $title, $text, $image, $index + 1));
        }
        $manager->flush();
    }
}
