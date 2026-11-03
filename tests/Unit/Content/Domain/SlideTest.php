<?php

declare(strict_types=1);

namespace App\Tests\Unit\Content\Domain;

use App\Content\Domain\ContentRuleViolation;
use App\Content\Domain\Slide;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class SlideTest extends TestCase
{
    #[TestWith([0])]
    #[TestWith([6])]
    public function testRefusesAnUnknownImage(int $image): void
    {
        $this->expectExceptionObject(new ContentRuleViolation(\sprintf('Le visuel est un numéro de 1 à 5 : %d.', $image)));
        new Slide('s', 'S', 'Texte.', $image, 1);
    }

    public function testUpdateRefusesAnUnknownImage(): void
    {
        $slide = new Slide('s', 'S', 'Texte.', 1, 1);

        $this->expectException(ContentRuleViolation::class);
        $slide->update('s', 'S', 'Texte.', 6, 1);
    }
}
