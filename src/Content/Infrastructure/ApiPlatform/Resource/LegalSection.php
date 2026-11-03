<?php

declare(strict_types=1);

namespace App\Content\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;

final readonly class LegalSection
{
    public function __construct(
        #[ApiProperty(required: true)]
        public string $title,
        #[ApiProperty(description: 'HTML de la section, sans son titre. Le HTML saisi dans l\'admin est échappé.', required: true)]
        public string $html,
    ) {
    }
}
