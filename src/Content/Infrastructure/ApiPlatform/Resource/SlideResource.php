<?php

declare(strict_types=1);

namespace App\Content\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Content\Domain\Slide;
use App\Content\Infrastructure\ApiPlatform\State\SlideCollectionProvider;
use App\Media\Domain\ImageUsage;
use App\Media\Infrastructure\ApiPlatform\ImageView;
use App\Media\Infrastructure\ApiPlatform\ImageViews;

#[ApiResource(
    shortName: 'Slide',
    description: 'Diapositive du carrousel de la page d\'accueil.',
    // image peut valoir null : sans cela, API Platform retire la clé alors que le schéma la déclare requise
    normalizationContext: ['skip_null_values' => false],
    operations: [
        new GetCollection(
            description: 'Liste les diapositives dans l\'ordre du carrousel, sans pagination.',
            provider: SlideCollectionProvider::class,
        ),
    ],
)]
final readonly class SlideResource
{
    public function __construct(
        #[ApiProperty(identifier: true, required: true)]
        public int $id,
        #[ApiProperty(required: true)]
        public string $slug,
        #[ApiProperty(required: true)]
        public string $title,
        #[ApiProperty(required: true)]
        public string $text,
        #[ApiProperty(description: 'Image de la diapositive (16:9), null tant que l\'admin n\'en a pas envoyé.', required: true)]
        public ?ImageView $image,
    ) {
    }

    public static function fromModel(Slide $slide, ImageViews $images): self
    {
        return new self((int) $slide->getId(), $slide->getSlug(), $slide->getTitle(), $slide->getText(), $images->view($slide->getImage(), ImageUsage::Slide));
    }
}
