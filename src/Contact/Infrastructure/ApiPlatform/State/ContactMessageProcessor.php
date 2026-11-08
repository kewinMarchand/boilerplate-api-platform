<?php

declare(strict_types=1);

namespace App\Contact\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Contact\Application\SendContactMessage;
use App\Contact\Domain\UnknownProduct;
use App\Contact\Infrastructure\ApiPlatform\Resource\ContactMessageResource;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @implements ProcessorInterface<ContactMessageResource, ContactMessageResource>
 */
final readonly class ContactMessageProcessor implements ProcessorInterface
{
    public function __construct(private SendContactMessage $sendContactMessage)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ContactMessageResource
    {
        try {
            $contactMessage = ($this->sendContactMessage)(
                trim((string) $data->name),
                trim((string) $data->email),
                trim((string) $data->message),
                $data->product,
            );
        } catch (UnknownProduct) {
            throw new ValidationException(new ConstraintViolationList([new ConstraintViolation(ContactMessageResource::PRODUCT_ERROR, null, [], $data, 'product', $data->product)]));
        }

        $data->id = $contactMessage->getId();
        $data->name = $contactMessage->getName();
        $data->email = $contactMessage->getEmail();
        $data->message = $contactMessage->getMessage();
        $data->product = $contactMessage->getProductSlug();
        $data->receivedAt = $contactMessage->getReceivedAt();

        return $data;
    }
}
