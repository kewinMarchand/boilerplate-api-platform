<?php

declare(strict_types=1);

namespace App\Blog\Infrastructure\Markdown;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Convertit le Markdown d'un article en HTML, avec les réglages des pages légales : HTML saisi échappé, liens javascript:
 * et data: retirés, pour que les fronts puissent injecter le résultat tel quel.
 */
final readonly class ArticleHtml
{
    private MarkdownConverter $converter;

    public function __construct()
    {
        $environment = new Environment(['html_input' => 'escape', 'allow_unsafe_links' => false]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $this->converter = new MarkdownConverter($environment);
    }

    public function render(string $markdown): string
    {
        return $this->converter->convert($markdown)->getContent();
    }
}
