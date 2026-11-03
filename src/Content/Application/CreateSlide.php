<?php

declare(strict_types=1);

namespace App\Content\Application;

use App\Content\Domain\ContentRuleViolation;
use App\Content\Domain\Slide;
use App\Content\Domain\SlideRepository;

final readonly class CreateSlide
{
    public function __construct(private SlideRepository $slides)
    {
    }

    /**
     * @throws ContentRuleViolation slug déjà pris, visuel inconnu
     */
    public function __invoke(SlideData $data): Slide
    {
        if (null !== $this->slides->findBySlug($data->slug)) {
            throw new ContentRuleViolation(\sprintf('Le slug « %s » est déjà pris par une autre diapositive.', $data->slug));
        }

        $slide = new Slide($data->slug, $data->title, $data->text, $data->image, $data->position);
        $this->slides->save($slide);

        return $slide;
    }
}
