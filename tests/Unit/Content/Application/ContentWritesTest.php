<?php

declare(strict_types=1);

namespace App\Tests\Unit\Content\Application;

use App\Content\Application\LegalPageData;
use App\Content\Application\UpdateLegalPage;
use App\Content\Domain\ContentRuleViolation;
use App\Content\Domain\LegalPage;
use App\Content\Domain\LegalPageRepository;
use App\Tests\Support\Identity;
use PHPUnit\Framework\TestCase;

final class ContentWritesTest extends TestCase
{
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

    private function legalPages(LegalPage ...$pages): LegalPageRepository
    {
        return new class($pages) implements LegalPageRepository {
            /**
             * @param array<LegalPage> $pages
             */
            public function __construct(private array $pages)
            {
            }

            public function findAll(): array
            {
                return array_values($this->pages);
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
