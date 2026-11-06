<?php

declare(strict_types=1);

namespace App\Content\Application;

use App\Content\Domain\ContentRuleViolation;
use App\Content\Domain\Slide;
use App\Content\Domain\SlideRepository;
use App\Media\Application\ChangeImage;
use App\Media\Domain\ImageUsage;
use App\Media\Domain\MediaRuleViolation;

final readonly class CreateSlide
{
    public function __construct(
        private SlideRepository $slides,
        private ChangeImage $changeImage,
    ) {
    }

    /**
     * @throws ContentRuleViolation slug déjà pris, image absente
     * @throws MediaRuleViolation   image refusée
     */
    public function __invoke(SlideData $data): Slide
    {
        if (null !== $this->slides->findBySlug($data->slug)) {
            throw new ContentRuleViolation(\sprintf('Le slug « %s » est déjà pris par une autre diapositive.', $data->slug));
        }
        $image = ($this->changeImage)(null, $data->imageContent, $data->imageAlt, $data->imageDecorative, ImageUsage::Slide)
            ?? throw new ContentRuleViolation('Une diapositive a besoin d\'une image.');

        $slide = new Slide($data->slug, $data->title, $data->text, $image, $data->position);
        $this->slides->save($slide);

        return $slide;
    }
}
