<?php

declare(strict_types=1);

namespace App\Content\Application;

use App\Content\Domain\ContentRuleViolation;
use App\Content\Domain\SlideRepository;

final readonly class DeleteSlide
{
    public function __construct(private SlideRepository $slides)
    {
    }

    /**
     * @throws ContentRuleViolation diapositive introuvable
     */
    public function __invoke(int $id): void
    {
        $this->slides->remove($this->slides->find($id) ?? throw new ContentRuleViolation('Cette diapositive n\'existe plus.'));
    }
}
