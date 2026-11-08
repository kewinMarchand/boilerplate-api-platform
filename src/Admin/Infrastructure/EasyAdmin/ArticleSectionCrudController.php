<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\EasyAdmin;

use App\Blog\Domain\ArticleTermKind;

final class ArticleSectionCrudController extends ArticleTermCrudController
{
    protected static function kind(): ArticleTermKind
    {
        return ArticleTermKind::Section;
    }
}
