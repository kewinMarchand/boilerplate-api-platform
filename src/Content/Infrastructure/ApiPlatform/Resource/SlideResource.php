<?php

declare(strict_types=1);

namespace App\Content\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Content\Domain\Slide;
use App\Content\Infrastructure\ApiPlatform\State\SlideCollectionProvider;

#[ApiResource(
    shortName: 'Slide',
    description: 'Diapositive du carrousel de la page d\'accueil.',
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
        #[ApiProperty(description: 'Numéro du visuel livré avec les fronts, de 1 à 5.', required: true)]
        public int $image,
    ) {
    }

    public static function fromModel(Slide $slide): self
    {
        return new self((int) $slide->getId(), $slide->getSlug(), $slide->getTitle(), $slide->getText(), $slide->getImage());
    }
}
