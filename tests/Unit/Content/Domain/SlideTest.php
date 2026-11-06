<?php

declare(strict_types=1);

namespace App\Tests\Unit\Content\Domain;

use App\Content\Domain\Slide;
use App\Tests\Support\TestImages;
use PHPUnit\Framework\TestCase;

final class SlideTest extends TestCase
{
    public function testUpdateReplacesItsImage(): void
    {
        $slide = new Slide('s', 'S', 'Texte.', TestImages::image('Avant'), 1);
        $after = TestImages::image('Après', str_repeat('b', 32));

        $slide->update('s', 'S', 'Texte.', $after, 1);

        self::assertEquals($after, $slide->getImage());
    }
}
