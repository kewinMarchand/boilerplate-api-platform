<?php

declare(strict_types=1);

namespace App\Tests\Unit\Blog\Application;

use App\Blog\Application\ArticleTermData;
use App\Blog\Application\CreateArticleTerm;
use App\Blog\Application\DeleteArticleTerm;
use App\Blog\Application\UpdateArticleTerm;
use App\Blog\Domain\Article;
use App\Blog\Domain\ArticleTerm;
use App\Blog\Domain\ArticleTermKind;
use App\Blog\Domain\BlogRuleViolation;
use App\Tests\Support\InMemoryArticleRepository;
use App\Tests\Support\InMemoryArticleTermRepository;
use App\Tests\Support\TestImages;
use PHPUnit\Framework\TestCase;

final class ArticleTermWritesTest extends TestCase
{
    private InMemoryArticleTermRepository $terms;
    private InMemoryArticleRepository $articles;
    private ArticleTerm $advice;
    private ArticleTerm $news;
    private ArticleTerm $guide;

    protected function setUp(): void
    {
        $this->advice = new ArticleTerm(ArticleTermKind::Section, 'conseils', 'Conseils', 1);
        $this->news = new ArticleTerm(ArticleTermKind::Section, 'actualites', 'Actualités', 2);
        $this->guide = new ArticleTerm(ArticleTermKind::Format, 'guide', 'Guide', 1);
        $this->terms = new InMemoryArticleTermRepository([$this->advice, $this->news, $this->guide]);
        $first = new Article('premier', 'Premier', 'Résumé.', new \DateTimeImmutable('2026-09-15'), TestImages::image());
        $first->classify($this->advice, $this->guide);
        $second = new Article('second', 'Second', 'Résumé.', new \DateTimeImmutable('2026-09-22'), TestImages::image());
        $second->classify($this->advice, null);
        $this->articles = new InMemoryArticleRepository([$first, $second]);
    }

    public function testCreatesASection(): void
    {
        $created = new CreateArticleTerm($this->terms)(ArticleTermKind::Section, $this->data('jardin', 'Jardin', 3));

        self::assertSame($created, $this->terms->findBySlug(ArticleTermKind::Section, 'jardin'));
        self::assertSame(['conseils', 'actualites', 'jardin'], array_map(static fn (ArticleTerm $term): string => $term->getSlug(), $this->terms->findByKind(ArticleTermKind::Section)));
    }

    public function testASlugIsUniqueWithinItsListOnly(): void
    {
        new CreateArticleTerm($this->terms)(ArticleTermKind::Format, $this->data('conseils', 'Conseils', 2));

        $this->expectExceptionObject(new BlogRuleViolation('Le slug « guide » est déjà pris par un autre format.'));
        new CreateArticleTerm($this->terms)(ArticleTermKind::Format, $this->data('guide', 'Guide bis', 3));
    }

    public function testUpdatesASectionAndRefusesTheSlugOfAnother(): void
    {
        new UpdateArticleTerm($this->terms)((int) $this->news->getId(), ArticleTermKind::Section, $this->data('actualites', 'Nouvelles', 0));
        self::assertSame(['Nouvelles', 0], [$this->news->getName(), $this->news->getPosition()]);

        $this->expectExceptionObject(new BlogRuleViolation('Le slug « conseils » est déjà pris par une autre rubrique.'));
        new UpdateArticleTerm($this->terms)((int) $this->news->getId(), ArticleTermKind::Section, $this->data('conseils', 'Nouvelles', 0));
    }

    public function testRefusesToUpdateATermThroughTheOtherList(): void
    {
        $this->expectExceptionObject(new BlogRuleViolation('Cette rubrique n\'existe plus.'));
        new UpdateArticleTerm($this->terms)((int) $this->guide->getId(), ArticleTermKind::Section, $this->data('guide', 'Guide', 1));
    }

    public function testRefusesToDeleteASectionUsedByArticles(): void
    {
        $this->expectExceptionObject(new BlogRuleViolation('La rubrique Conseils est utilisée par 2 articles.'));
        $this->delete($this->advice, ArticleTermKind::Section);
    }

    public function testRefusesToDeleteAFormatUsedByAnArticle(): void
    {
        $this->expectExceptionObject(new BlogRuleViolation('Le format Guide est utilisé par 1 article.'));
        $this->delete($this->guide, ArticleTermKind::Format);
    }

    public function testDeletesAnUnusedSection(): void
    {
        $this->delete($this->news, ArticleTermKind::Section);

        self::assertNull($this->terms->findBySlug(ArticleTermKind::Section, 'actualites'));
    }

    public function testAnArticleRefusesATermOfTheOtherList(): void
    {
        $this->expectExceptionObject(new BlogRuleViolation('La rubrique choisie n\'est pas une rubrique.'));
        new Article('x', 'X', '', new \DateTimeImmutable(), TestImages::image())->classify($this->guide, null);
    }

    private function delete(ArticleTerm $term, ArticleTermKind $kind): void
    {
        new DeleteArticleTerm($this->terms, $this->articles)((int) $term->getId(), $kind);
    }

    private function data(string $slug, string $name, int $position): ArticleTermData
    {
        $data = new ArticleTermData();
        $data->slug = $slug;
        $data->name = $name;
        $data->position = $position;

        return $data;
    }
}
