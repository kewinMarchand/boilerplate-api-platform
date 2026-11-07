<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Doctrine\Fixtures;

use App\Catalog\Domain\Category;
use App\Catalog\Domain\Exposure;
use App\Catalog\Domain\Price;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\Size;
use App\Media\Domain\ImageUsage;
use App\Media\Infrastructure\Fixtures\DemoImages;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Jardinerie tropicale fictive : arbre de catégories sur 3 niveaux, 24 produits répartis dans les feuilles.
 */
final class CatalogFixtures extends Fixture
{
    public function __construct(private readonly DemoImages $images)
    {
    }

    /**
     * slug => [nom, slug du parent].
     */
    private const array CATEGORIES = [
        'plantes-interieur' => ['Plantes d’intérieur', null],
        'feuillages' => ['Feuillages', 'plantes-interieur'],
        'monstera' => ['Monstera', 'feuillages'],
        'fougeres' => ['Fougères', 'feuillages'],
        'plantes-a-fleurs' => ['Plantes à fleurs', 'plantes-interieur'],
        'anthurium' => ['Anthurium', 'plantes-a-fleurs'],
        'strelitzia' => ['Strelitzia', 'plantes-a-fleurs'],
        'plantes-exterieur' => ['Plantes d’extérieur', null],
        'palmiers' => ['Palmiers', 'plantes-exterieur'],
        'arbustes-a-fleurs' => ['Arbustes à fleurs', 'plantes-exterieur'],
        'heliconia' => ['Heliconia', 'arbustes-a-fleurs'],
        'calliandra' => ['Calliandra', 'arbustes-a-fleurs'],
        'plantes-aquatiques' => ['Plantes aquatiques', null],
        'nenuphars' => ['Nénuphars', 'plantes-aquatiques'],
    ];

    /**
     * [slug, nom, catégorie, prix en centimes, exposition, taille, en stock].
     */
    private const array PRODUCTS = [
        ['monstera-deliciosa', 'Monstera deliciosa', 'monstera', 3490, 'mi-ombre', 'M', true],
        ['monstera-adansonii', 'Monstera adansonii', 'monstera', 1990, 'mi-ombre', 'S', true],
        ['monstera-thai-constellation', 'Monstera Thai Constellation', 'monstera', 12900, 'mi-ombre', 'L', false],
        ['fougere-de-boston', 'Fougère de Boston', 'fougeres', 1590, 'ombre', 'M', true],
        ['asplenium-nidus', 'Asplenium nidus', 'fougeres', 1290, 'ombre', 'S', true],
        ['fougere-arborescente-dicksonia', 'Fougère arborescente Dicksonia', 'fougeres', 8900, 'mi-ombre', 'L', true],
        ['anthurium-andreanum-rouge', 'Anthurium andreanum rouge', 'anthurium', 2490, 'mi-ombre', 'M', true],
        ['anthurium-clarinervium', 'Anthurium clarinervium', 'anthurium', 3990, 'ombre', 'S', false],
        ['anthurium-blanc-champion', 'Anthurium blanc Champion', 'anthurium', 2690, 'mi-ombre', 'M', true],
        ['strelitzia-reginae', 'Strelitzia reginae, oiseau de paradis', 'strelitzia', 4990, 'soleil', 'L', true],
        ['strelitzia-nicolai', 'Strelitzia nicolai', 'strelitzia', 6990, 'soleil', 'L', true],
        ['strelitzia-reginae-jeune-plant', 'Strelitzia reginae, jeune plant', 'strelitzia', 1890, 'soleil', 'S', true],
        ['palmier-de-chine', 'Palmier de Chine (Trachycarpus fortunei)', 'palmiers', 7990, 'soleil', 'L', true],
        ['palmier-bleu-du-mexique', 'Palmier bleu du Mexique (Brahea armata)', 'palmiers', 8990, 'soleil', 'M', true],
        ['palmier-nain', 'Palmier nain (Chamaerops humilis)', 'palmiers', 4490, 'soleil', 'M', false],
        ['heliconia-rostrata', 'Heliconia rostrata', 'heliconia', 5490, 'soleil', 'L', true],
        ['heliconia-psittacorum', 'Heliconia psittacorum', 'heliconia', 2990, 'soleil', 'M', true],
        ['heliconia-wagneriana', 'Heliconia wagneriana', 'heliconia', 5990, 'mi-ombre', 'L', false],
        ['calliandra-haematocephala', 'Calliandra haematocephala', 'calliandra', 3490, 'soleil', 'M', true],
        ['calliandra-surinamensis', 'Calliandra surinamensis', 'calliandra', 2290, 'soleil', 'S', true],
        ['calliandra-tweedii', 'Calliandra tweedii', 'calliandra', 4790, 'soleil', 'L', true],
        ['nenuphar-blanc-rustique', 'Nénuphar blanc rustique (Nymphaea alba)', 'nenuphars', 1490, 'soleil', 'S', true],
        ['nenuphar-bleu-du-nil', 'Nénuphar bleu du Nil (Nymphaea caerulea)', 'nenuphars', 2490, 'soleil', 'M', true],
        ['lotus-sacre', 'Lotus sacré (Nelumbo nucifera)', 'nenuphars', 3990, 'soleil', 'L', false],
    ];

    /** Produits du carrousel de la page d'accueil. */
    private const array FEATURED = ['heliconia-rostrata', 'anthurium-andreanum-rouge', 'calliandra-haematocephala', 'strelitzia-reginae', 'nenuphar-bleu-du-nil'];

    public function load(ObjectManager $manager): void
    {
        /** @var array<string, Category> $categories */
        $categories = [];
        $position = 0;
        foreach (self::CATEGORIES as $slug => [$name, $parentSlug]) {
            $categories[$slug] = new Category($slug, $name, ++$position, null === $parentSlug ? null : $categories[$parentSlug]);
            $manager->persist($categories[$slug]);
        }

        foreach (self::PRODUCTS as $index => [$slug, $name, $categorySlug, $price, $exposure, $size, $inStock]) {
            $manager->persist(new Product(
                $slug,
                $name,
                $categories[$categorySlug],
                Price::fromCents($price),
                Exposure::from($exposure),
                Size::from($size),
                $inStock,
                $this->images->import('product-'.($index % 12 + 1), ImageUsage::Product, $name),
                \in_array($slug, self::FEATURED, true),
            ));
        }

        $manager->flush();
    }
}
