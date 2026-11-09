<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Locale;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

/**
 * Documente la langue du contenu (ADR 0015) sur chaque lecture : paramètre `locale`, en-tête Accept-Language et
 * Content-Language de la réponse. Écrits une fois ici plutôt que répétés dans chaque ressource.
 */
#[AsDecorator(decorates: 'api_platform.openapi.factory')]
final readonly class LocaleOpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(
        #[AutowireDecorated]
        private OpenApiFactoryInterface $inner,
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->inner)($context);
        $paths = $openApi->getPaths();
        foreach ($paths->getPaths() as $path => $item) {
            $get = $item instanceof PathItem ? $item->getGet() : null;
            if (null === $get) {
                continue;
            }
            $get = $get
                ->withParameter(new Parameter(ContentLocale::PARAMETER, 'query', 'Langue du contenu, code BCP 47 court (fr, en). L\'emporte sur Accept-Language. Langue non activée : ignoré.', schema: ['type' => 'string']))
                ->withParameter(new Parameter('Accept-Language', 'header', 'Langues acceptées : la première langue activée du site qui y figure est servie, sinon la langue par défaut. Champ non traduit : valeur de la langue par défaut.', schema: ['type' => 'string']));
            $response = $get->getResponses()[200] ?? null;
            if ($response instanceof Response) {
                $headers = $response->getHeaders() ?? new \ArrayObject();
                $headers['Content-Language'] = ['description' => 'Langue du contenu servi.', 'schema' => ['type' => 'string']];
                $get = $get->withResponse(200, $response->withHeaders($headers));
            }
            $paths->addPath($path, $item->withGet($get));
        }

        return $openApi;
    }
}
