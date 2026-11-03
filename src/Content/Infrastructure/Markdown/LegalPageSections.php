<?php

declare(strict_types=1);

namespace App\Content\Infrastructure\Markdown;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Node\Inline\AbstractStringContainer;
use League\CommonMark\Node\Node;
use League\CommonMark\Parser\MarkdownParser;
use League\CommonMark\Renderer\HtmlRenderer;

/**
 * Convertit le Markdown d'une page légale en HTML, découpé par titres de niveau 2 : ce qui précède le premier titre
 * forme l'introduction, chaque titre ouvre une section. Le HTML saisi est échappé et les liens javascript: et
 * data: sont retirés, pour que les fronts puissent injecter le résultat tel quel.
 */
final readonly class LegalPageSections
{
    private MarkdownParser $parser;
    private HtmlRenderer $renderer;

    public function __construct()
    {
        $environment = new Environment(['html_input' => 'escape', 'allow_unsafe_links' => false]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $this->parser = new MarkdownParser($environment);
        $this->renderer = new HtmlRenderer($environment);
    }

    /**
     * @return array{introduction: string, sections: list<array{title: string, html: string}>}
     */
    public function split(string $markdown): array
    {
        $introduction = [];
        /** @var list<string> $titles */
        $titles = [];
        /** @var list<list<Node>> $bodies */
        $bodies = [];
        foreach ($this->parser->parse($markdown)->children() as $node) {
            if ($node instanceof Heading && 2 === $node->getLevel()) {
                $titles[] = self::text($node);
                $bodies[] = [];
            } elseif ([] === $bodies) {
                $introduction[] = $node;
            } else {
                $bodies[\count($bodies) - 1][] = $node;
            }
        }

        $sections = [];
        foreach ($titles as $index => $title) {
            $sections[] = ['title' => $title, 'html' => $this->renderer->renderNodes($bodies[$index])];
        }

        return ['introduction' => $this->renderer->renderNodes($introduction), 'sections' => $sections];
    }

    private static function text(Node $heading): string
    {
        $text = '';
        foreach ($heading->iterator() as $node) {
            if ($node instanceof AbstractStringContainer) {
                $text .= $node->getLiteral();
            }
        }

        return $text;
    }
}
