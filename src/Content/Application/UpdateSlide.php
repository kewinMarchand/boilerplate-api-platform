<?php

declare(strict_types=1);

namespace App\Content\Application;

use App\Content\Domain\ContentRuleViolation;
use App\Content\Domain\SlideRepository;

final readonly class UpdateSlide
{
    public function __construct(private SlideRepository $slides)
    {
    }

    /**
     * @throws ContentRuleViolation diapositive introuvable, slug déjà pris, visuel inconnu
     */
    public function __invoke(int $id, SlideData $data): void
    {
        $slide = $this->slides->find($id) ?? throw new ContentRuleViolation('Cette diapositive n\'existe plus.');
        $sameSlug = $this->slides->findBySlug($data->slug);
        if (null !== $sameSlug && $sameSlug !== $slide) {
            throw new ContentRuleViolation(\sprintf('Le slug « %s » est déjà pris par une autre diapositive.', $data->slug));
        }

        $slide->update($data->slug, $data->title, $data->text, $data->image, $data->position);
        $this->slides->save($slide);
    }
}
