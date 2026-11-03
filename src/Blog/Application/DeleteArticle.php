<?php

declare(strict_types=1);

namespace App\Blog\Application;

use App\Blog\Domain\ArticleRepository;
use App\Blog\Domain\BlogRuleViolation;

final readonly class DeleteArticle
{
    public function __construct(private ArticleRepository $articles)
    {
    }

    /**
     * @throws BlogRuleViolation article introuvable
     */
    public function __invoke(int $id): void
    {
        $this->articles->remove($this->articles->find($id) ?? throw new BlogRuleViolation('Cet article n\'existe plus.'));
    }
}
