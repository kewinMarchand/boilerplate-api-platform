<?php

declare(strict_types=1);

namespace App\Tests\Unit\Content\Application;

use App\Content\Application\CreateSlide;
use App\Content\Application\DeleteSlide;
use App\Content\Application\LegalPageData;
use App\Content\Application\SlideData;
use App\Content\Application\UpdateLegalPage;
use App\Content\Application\UpdateSlide;
use App\Content\Domain\ContentRuleViolation;
use App\Content\Domain\LegalPage;
use App\Content\Domain\LegalPageRepository;
use App\Content\Domain\Slide;
use App\Tests\Support\Identity;
use App\Tests\Support\InMemorySlideRepository;
use App\Tests\Support\TestImages;
use PHPUnit\Framework\TestCase;

final class ContentWritesTest extends TestCase
{
    private InMemorySlideRepository $slides;
    private Slide $first;

    protected function setUp(): void
    {
        $this->first = new Slide('heliconia', 'Heliconia', 'Texte.', TestImages::image('Heliconia en fleur'), 1);
        $this->slides = new InMemorySlideRepository([$this->first, new Slide('anthurium', 'Anthurium', 'Texte.', TestImages::image(), 2)]);
    }

    public function testCreatesASlide(): void
    {
        $created = new CreateSlide($this->slides, TestImages::changeImage())($this->slideData('strelitzia', 0));

        self::assertSame([$created, $this->first], \array_slice($this->slides->findInOrder(), 0, 2));
    }

    public function testRefusesATakenSlugOnCreation(): void
    {
        $this->expectExceptionObject(new ContentRuleViolation('Le slug « heliconia » est déjà pris par une autre diapositive.'));
        new CreateSlide($this->slides, TestImages::changeImage())($this->slideData('heliconia', 3));
    }

    public function testRefusesTheSlugOfAnotherSlideOnUpdate(): void
    {
        $this->expectException(ContentRuleViolation::class);
        new UpdateSlide($this->slides, TestImages::changeImage())((int) $this->first->getId(), $this->slideData('anthurium', 1));
    }

    public function testUpdatesASlideAndKeepsItsOwnSlug(): void
    {
        new UpdateSlide($this->slides, TestImages::changeImage())((int) $this->first->getId(), $this->slideData('heliconia', 5));

        self::assertSame(5, $this->first->getPosition());
    }

    public function testCreatingASlideStoresItsImage(): void
    {
        $data = $this->slideData('strelitzia', 3);
        $data->imageContent = TestImages::PHOTO;
        $data->imageAlt = 'Fleur orange et bleue';

        $created = new CreateSlide($this->slides, TestImages::changeImage())($data);

        self::assertSame('Fleur orange et bleue', $created->getImage()?->alt);
    }

    public function testRefusesASlideWithoutImage(): void
    {
        $data = $this->slideData('strelitzia', 3);
        $data->imageContent = null;

        $this->expectExceptionObject(new ContentRuleViolation('Une diapositive a besoin d\'une image.'));
        new CreateSlide($this->slides, TestImages::changeImage())($data);
    }

    public function testUpdateWithoutFileKeepsTheImageAndChangesItsAlt(): void
    {
        $key = $this->first->getImage()?->key;
        $data = $this->slideData('heliconia', 1);
        $data->imageContent = null;
        $data->imageAlt = 'Bractées rouges';

        new UpdateSlide($this->slides, TestImages::changeImage())((int) $this->first->getId(), $data);

        self::assertSame([$key, 'Bractées rouges'], [$this->first->getImage()?->key, $this->first->getImage()?->alt]);
    }

    public function testDeletesASlide(): void
    {
        new DeleteSlide($this->slides)((int) $this->first->getId());

        self::assertNull($this->slides->findBySlug('heliconia'));
    }

    public function testUpdatesALegalPage(): void
    {
        $page = new LegalPage('mentions-legales', 'Mentions légales', '## Éditeur');
        Identity::assign($page, 1);
        $data = new LegalPageData();
        $data->title = 'Mentions';
        $data->body = '## Hébergeur';

        new UpdateLegalPage($this->legalPages($page))(1, $data);

        self::assertSame(['mentions-legales', 'Mentions', '## Hébergeur'], [$page->getSlug(), $page->getTitle(), $page->getBody()]);
    }

    public function testRefusesToUpdateAMissingLegalPage(): void
    {
        $this->expectException(ContentRuleViolation::class);
        new UpdateLegalPage($this->legalPages())(1, new LegalPageData());
    }

    private function slideData(string $slug, int $position): SlideData
    {
        $data = new SlideData();
        $data->slug = $slug;
        $data->title = 'Titre';
        $data->text = 'Texte.';
        $data->imageAlt = 'Photo';
        $data->position = $position;
        $data->imageContent = TestImages::PHOTO;

        return $data;
    }

    private function legalPages(LegalPage ...$pages): LegalPageRepository
    {
        return new class($pages) implements LegalPageRepository {
            /**
             * @param array<LegalPage> $pages
             */
            public function __construct(private array $pages)
            {
            }

            public function find(int $id): ?LegalPage
            {
                return array_find($this->pages, static fn (LegalPage $page): bool => $page->getId() === $id);
            }

            public function findBySlug(string $slug): ?LegalPage
            {
                return array_find($this->pages, static fn (LegalPage $page): bool => $page->getSlug() === $slug);
            }

            public function save(LegalPage $page): void
            {
            }
        };
    }
}
