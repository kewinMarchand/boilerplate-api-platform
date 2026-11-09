<?php

declare(strict_types=1);

namespace App\Site\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Media\Domain\ImageUsage;
use App\Media\Infrastructure\ApiPlatform\ImageViews;
use App\Shared\Infrastructure\Locale\ContentLocale;
use App\Site\Application\GetSiteIdentity;
use App\Site\Infrastructure\ApiPlatform\Resource\SiteIcon;
use App\Site\Infrastructure\ApiPlatform\Resource\SiteIdentityResource;

/**
 * @implements ProviderInterface<SiteIdentityResource>
 */
final readonly class SiteIdentityProvider implements ProviderInterface
{
    public function __construct(
        private GetSiteIdentity $getSiteIdentity,
        private ImageViews $images,
        private ContentLocale $contentLocale,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SiteIdentityResource
    {
        $identity = ($this->getSiteIdentity)();

        return SiteIdentityResource::fromModel(
            $identity,
            $this->images->view($identity->getLogo(), ImageUsage::Logo),
            $this->images->view($identity->getShareImage(), ImageUsage::Share),
            SiteIcon::fromModel($identity->getIcon(), $this->images),
            $this->contentLocale->current(),
            $this->contentLocale->locales(),
        );
    }
}
