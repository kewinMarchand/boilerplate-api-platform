<?php

declare(strict_types=1);

namespace App\Tests\Unit\Blog\Application;

use App\Blog\Application\ArticleData;
use App\Blog\Application\CreateArticle;
use App\Blog\Application\DeleteArticle;
use App\Blog\Application\UpdateArticle;
use App\Blog\Domain\Article;
use App\Blog\Domain\ArticleTerm;
use App\Blog\Domain\ArticleTermKind;
use App\Blog\Domain\BlogRuleViolation;
use App\Media\Application\StoreAudio;
use App\Media\Domain\AudioFormat;
use App\Media\Domain\MediaRuleViolation;
use App\Media\Infrastructure\Audio\HeaderAudioInspector;
use App\Tests\Support\InMemoryArticleRepository;
use App\Tests\Support\InMemoryArticleTermRepository;
use App\Tests\Support\InMemoryMediaStorage;
use App\Tests\Support\TestImages;
use PHPUnit\Framework\TestCase;

final class ArticleWritesTest extends TestCase
{
    private const string AUDIO = __DIR__.'/../../../Fixtures/audio/beep-3s.ogg';

    private InMemoryArticleRepository $articles;
    private InMemoryArticleTermRepository $terms;
    private InMemoryMediaStorage $storage;
    private Article $first;
    private ArticleTerm $advice;
    private ArticleTerm $guide;

    protected function setUp(): void
    {
        $this->first = new Article('premier', 'Premier', 'Résumé.', new \DateTimeImmutable('2026-09-15'), TestImages::image());
        $this->articles = new InMemoryArticleRepository([$this->first, new Article('second', 'Second', 'Résumé.', new \DateTimeImmutable('2026-09-22'), TestImages::image())]);
        $this->advice = new ArticleTerm(ArticleTermKind::Section, 'conseils', 'Conseils', 1);
        $this->guide = new ArticleTerm(ArticleTermKind::Format, 'guide', 'Guide', 1);
        $this->terms = new InMemoryArticleTermRepository([$this->advice, $this->guide]);
        $this->storage = new InMemoryMediaStorage();
    }

    public function testCreatesAnArticle(): void
    {
        $created = $this->create($this->data('nouveau'));

        self::assertSame($created, $this->articles->findBySlug('nouveau'));
        self::assertSame('2026-10-01', $created->getPublishedAt()->format('Y-m-d'));
        self::assertSame([null, null, null, ''], [$created->getSection(), $created->getFormat(), $created->getAudio(), $created->getSpeaker()]);
    }

    public function testRefusesATakenSlugOnCreation(): void
    {
        $this->expectExceptionObject(new BlogRuleViolation('Le slug « premier » est déjà pris par un autre article.'));
        $this->create($this->data('premier'));
    }

    public function testCreatesAnArticleWithItsSectionFormatAudioSpeakerAndMeta(): void
    {
        $data = $this->data('nouveau');
        $data->sectionId = $this->advice->getId();
        $data->formatId = $this->guide->getId();
        $data->audioPath = self::AUDIO;
        $data->speaker = ' Présenté par Hina Teriierooiterai ';
        $data->metaTitle = 'Titre court';
        $data->metaDescription = 'Description.';

        $created = $this->create($data);

        self::assertSame([$this->advice, $this->guide], [$created->getSection(), $created->getFormat()]);
        self::assertSame([AudioFormat::Ogg, 3], [$created->getAudio()?->format, $created->getAudio()?->durationSeconds]);
        self::assertArrayHasKey((string) $created->getAudio()?->fileName(), $this->storage->files);
        self::assertSame(['Présenté par Hina Teriierooiterai', 'Titre court', 'Description.'], [$created->getSpeaker(), $created->getMetaTitle(), $created->getMetaDescription()]);
    }

    public function testRefusesASectionThatIsAFormat(): void
    {
        $data = $this->data('nouveau');
        $data->sectionId = $this->guide->getId();

        $this->expectExceptionObject(new BlogRuleViolation('Cette rubrique n\'existe plus.'));
        $this->create($data);
    }

    public function testRefusesAMissingFormat(): void
    {
        $data = $this->data('nouveau');
        $data->formatId = 99;

        $this->expectExceptionObject(new BlogRuleViolation('Ce format n\'existe plus.'));
        $this->create($data);
    }

    public function testRefusesAFileThatIsNotAudio(): void
    {
        $data = $this->data('nouveau');
        $data->audioPath = __DIR__.'/../../../Fixtures/audio/video-1s.mp4';

        $this->expectExceptionObject(new MediaRuleViolation('Le fichier ne se lit pas comme un fichier audio MP3, M4A ou OGG.'));
        $this->create($data);
    }

    public function testRefusesASpeakerOverEightyCharacters(): void
    {
        $data = $this->data('nouveau');
        $data->speaker = str_repeat('é', 81);

        $this->expectExceptionObject(new BlogRuleViolation('L\'intervenant compte 80 caractères au plus.'));
        $this->create($data);
    }

    public function testUpdatesAnArticleAndKeepsItsOwnSlug(): void
    {
        $data = $this->data('premier');
        $data->title = 'Premier, revu';
        $this->update($data);

        self::assertSame('Premier, revu', $this->first->getTitle());
    }

    public function testRefusesTheSlugOfAnotherArticleOnUpdate(): void
    {
        $this->expectException(BlogRuleViolation::class);
        $this->update($this->data('second'));
    }

    public function testKeepsTheAudioWithoutANewFileThenRemovesIt(): void
    {
        $data = $this->data('premier');
        $data->audioPath = self::AUDIO;
        $this->update($data);
        $audio = $this->first->getAudio();
        self::assertNotNull($audio);

        $this->update($this->data('premier'));
        self::assertEquals($audio, $this->first->getAudio());

        $data = $this->data('premier');
        $data->removeAudio = true;
        $data->audioPath = self::AUDIO;
        $this->update($data);
        self::assertNull($this->first->getAudio());
    }

    public function testDeletesAnArticle(): void
    {
        new DeleteArticle($this->articles)((int) $this->first->getId());

        self::assertNull($this->articles->findBySlug('premier'));
    }

    public function testRefusesToDeleteAMissingArticle(): void
    {
        $this->expectException(BlogRuleViolation::class);
        new DeleteArticle($this->articles)(99);
    }

    private function create(ArticleData $data): Article
    {
        return new CreateArticle($this->articles, $this->terms, TestImages::changeImage(), $this->storeAudio())($data);
    }

    private function update(ArticleData $data): void
    {
        new UpdateArticle($this->articles, $this->terms, TestImages::changeImage(), $this->storeAudio())((int) $this->first->getId(), $data);
    }

    private function storeAudio(): StoreAudio
    {
        return new StoreAudio(new HeaderAudioInspector(), $this->storage);
    }

    private function data(string $slug): ArticleData
    {
        $data = new ArticleData();
        $data->slug = $slug;
        $data->title = 'Titre';
        $data->excerpt = 'Résumé.';
        $data->publishedAt = new \DateTimeImmutable('2026-10-01');
        $data->imageContent = TestImages::PHOTO;
        $data->imageDecorative = true;

        return $data;
    }
}
