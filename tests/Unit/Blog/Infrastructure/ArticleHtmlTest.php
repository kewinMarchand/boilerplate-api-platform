<?php

declare(strict_types=1);

namespace App\Tests\Unit\Blog\Infrastructure;

use App\Blog\Infrastructure\Markdown\ArticleHtml;
use PHPUnit\Framework\TestCase;

final class ArticleHtmlTest extends TestCase
{
    public function testConvertsMarkdownToHtml(): void
    {
        self::assertSame("<h2>Titre</h2>\n<p>Un <em>texte</em>.</p>\n", (new ArticleHtml())->render("## Titre\n\nUn *texte*."));
    }

    public function testEscapesTypedHtmlAndRemovesUnsafeLinks(): void
    {
        $html = (new ArticleHtml())->render("<script>alert(1)</script>\n\n[lien](javascript:alert(1))");

        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
        self::assertStringNotContainsString('javascript:', $html);
    }

    public function testRendersNothingForAnEmptyText(): void
    {
        self::assertSame('', (new ArticleHtml())->render(''));
    }
}
