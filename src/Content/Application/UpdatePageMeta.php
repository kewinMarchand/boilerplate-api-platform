<?php

declare(strict_types=1);

namespace App\Content\Application;

use App\Content\Domain\ContentRuleViolation;
use App\Content\Domain\PageMetaRepository;

final readonly class UpdatePageMeta
{
    public function __construct(private PageMetaRepository $metas)
    {
    }

    /**
     * @throws ContentRuleViolation page introuvable
     */
    public function __invoke(int $id, PageMetaData $data): void
    {
        $meta = $this->metas->find($id) ?? throw new ContentRuleViolation('Cette page n\'existe plus.');
        $meta->update($data->title, $data->description);
        $this->metas->save($meta);
    }
}
