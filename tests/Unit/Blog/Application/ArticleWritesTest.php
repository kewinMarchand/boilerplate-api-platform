<?php

declare(strict_types=1);

namespace App\Tests\Unit\Blog\Application;

use App\Blog\Application\ArticleData;
use App\Blog\Application\CreateArticle;
use App\Blog\Application\DeleteArticle;
use App\Blog\Application\UpdateArticle;
use App\Blog\Domain\Article;
use App\Blog\Domain\BlogRuleViolation;
use App\Tests\Support\InMemoryArticleRepository;
use PHPUnit\Framework\TestCase;

final class ArticleWritesTest extends TestCase
{
    private InMemoryArticleRepository $articles;
    private Article $first;

    protected function setUp(): void
    {
        $this->first = new Article('premier', 'Premier', 'Résumé.', new \DateTimeImmutable('2026-09-15'));
        $this->articles = new InMemoryArticleRepository([$this->first, new Article('second', 'Second', 'Résumé.', new \DateTimeImmutable('2026-09-22'))]);
    }

    public function testCreatesAnArticle(): void
    {
        $created = new CreateArticle($this->articles)($this->data('nouveau'));

        self::assertSame($created, $this->articles->findBySlug('nouveau'));
        self::assertSame('2026-10-01', $created->getPublishedAt()->format('Y-m-d'));
    }

    public function testRefusesATakenSlugOnCreation(): void
    {
        $this->expectExceptionObject(new BlogRuleViolation('Le slug « premier » est déjà pris par un autre article.'));
        new CreateArticle($this->articles)($this->data('premier'));
    }

    public function testUpdatesAnArticleAndKeepsItsOwnSlug(): void
    {
        $data = $this->data('premier');
        $data->title = 'Premier, revu';
        new UpdateArticle($this->articles)((int) $this->first->getId(), $data);

        self::assertSame('Premier, revu', $this->first->getTitle());
    }

    public function testRefusesTheSlugOfAnotherArticleOnUpdate(): void
    {
        $this->expectException(BlogRuleViolation::class);
        new UpdateArticle($this->articles)((int) $this->first->getId(), $this->data('second'));
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

    private function data(string $slug): ArticleData
    {
        $data = new ArticleData();
        $data->slug = $slug;
        $data->title = 'Titre';
        $data->excerpt = 'Résumé.';
        $data->publishedAt = new \DateTimeImmutable('2026-10-01');

        return $data;
    }
}
