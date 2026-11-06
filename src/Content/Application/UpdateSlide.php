<?php

declare(strict_types=1);

namespace App\Content\Application;

use App\Content\Domain\ContentRuleViolation;
use App\Content\Domain\SlideRepository;
use App\Media\Application\ChangeImage;
use App\Media\Domain\ImageUsage;
use App\Media\Domain\MediaRuleViolation;

final readonly class UpdateSlide
{
    public function __construct(
        private SlideRepository $slides,
        private ChangeImage $changeImage,
    ) {
    }

    /**
     * @throws ContentRuleViolation diapositive introuvable, slug déjà pris, image absente
     * @throws MediaRuleViolation   image refusée
     */
    public function __invoke(int $id, SlideData $data): void
    {
        $slide = $this->slides->find($id) ?? throw new ContentRuleViolation('Cette diapositive n\'existe plus.');
        $sameSlug = $this->slides->findBySlug($data->slug);
        if (null !== $sameSlug && $sameSlug !== $slide) {
            throw new ContentRuleViolation(\sprintf('Le slug « %s » est déjà pris par une autre diapositive.', $data->slug));
        }
        $image = ($this->changeImage)($slide->getImage(), $data->imageContent, $data->imageAlt, $data->imageDecorative, ImageUsage::Slide)
            ?? throw new ContentRuleViolation('Une diapositive a besoin d\'une image.');

        $slide->update($data->slug, $data->title, $data->text, $image, $data->position);
        $this->slides->save($slide);
    }
}
