<?php

declare(strict_types=1);

namespace App\Tests\Unit\Content\Infrastructure;

use App\Content\Infrastructure\Markdown\LegalPageSections;
use PHPUnit\Framework\TestCase;

final class LegalPageSectionsTest extends TestCase
{
    public function testSplitsByLevelTwoHeadings(): void
    {
        $content = new LegalPageSections()->split("Avant.\n\n## Éditeur\n\nExemple **SAS**.\n\n### Détail\n\n- un ;\n- deux.\n\n## Hébergeur `OVH`\n\nParis.");

        self::assertSame([
            'introduction' => '<p>Avant.</p>',
            'sections' => [
                ['title' => 'Éditeur', 'html' => "<p>Exemple <strong>SAS</strong>.</p>\n<h3>Détail</h3>\n<ul>\n<li>un ;</li>\n<li>deux.</li>\n</ul>"],
                ['title' => 'Hébergeur OVH', 'html' => '<p>Paris.</p>'],
            ],
        ], $content);
    }

    public function testHasAnEmptyIntroductionWhenTheBodyStartsWithAHeading(): void
    {
        self::assertSame('', new LegalPageSections()->split("## Éditeur\n\nTexte.")['introduction']);
    }

    public function testEscapesHtmlAndDropsUnsafeLinks(): void
    {
        $content = new LegalPageSections()->split("<script>alert(1)</script>\n\n[clic](javascript:alert(1)) <b>gras</b>");

        self::assertSame("&lt;script&gt;alert(1)&lt;/script&gt;\n<p><a>clic</a> &lt;b&gt;gras&lt;/b&gt;</p>", $content['introduction']);
    }
}
