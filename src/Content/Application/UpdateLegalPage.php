<?php

declare(strict_types=1);

namespace App\Content\Application;

use App\Content\Domain\ContentRuleViolation;
use App\Content\Domain\LegalPageRepository;

final readonly class UpdateLegalPage
{
    public function __construct(private LegalPageRepository $pages)
    {
    }

    /**
     * @throws ContentRuleViolation page introuvable
     */
    public function __invoke(int $id, LegalPageData $data): void
    {
        $page = $this->pages->find($id) ?? throw new ContentRuleViolation('Cette page n\'existe plus.');
        $page->update($data->title, $data->body);
        $page->changeTranslations($data->translations);
        $this->pages->save($page);
    }
}
