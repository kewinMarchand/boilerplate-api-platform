<?php

declare(strict_types=1);

namespace App\Contact\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Contact\Infrastructure\ApiPlatform\State\ContactMessageProcessor;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'ContactMessage',
    description: 'Message envoyé depuis le formulaire de contact.',
    operations: [
        new Post(
            description: 'Enregistre un message de contact, ou une demande de renseignement sur un produit, puis l\'envoie par mail. Répond 422 avec la liste des violations si un champ est invalide ou si le produit n\'existe pas ; un mail non envoyé ne change pas la réponse.',
            processor: ContactMessageProcessor::class,
        ),
    ],
)]
final class ContactMessageResource
{
    #[ApiProperty(identifier: true, writable: false)]
    public ?int $id = null;

    #[Assert\NotNull(message: self::NAME_ERROR)]
    #[Assert\Length(min: 2, max: 255, minMessage: self::NAME_ERROR, maxMessage: self::NAME_MAX_ERROR, normalizer: 'trim')]
    public ?string $name = null;

    #[Assert\NotBlank(message: self::EMAIL_ERROR)]
    #[Assert\Email(message: self::EMAIL_ERROR, mode: Assert\Email::VALIDATION_MODE_HTML5)]
    #[Assert\Length(max: 255, maxMessage: self::EMAIL_MAX_ERROR)]
    public ?string $email = null;

    #[Assert\NotNull(message: self::MESSAGE_ERROR)]
    #[Assert\Length(min: 10, max: 5000, minMessage: self::MESSAGE_ERROR, maxMessage: self::MESSAGE_MAX_ERROR, normalizer: 'trim')]
    public ?string $message = null;

    // Sans contrainte, API Platform laisse passer l'erreur de type du serializer en 500 au lieu de la 422 des autres champs
    #[ApiProperty(description: 'Slug du produit d\'une demande de renseignement ; null ou absent pour un message de contact simple.')]
    #[Assert\Type('string')]
    public ?string $product = null;

    #[ApiProperty(writable: false)]
    public ?\DateTimeImmutable $receivedAt = null;

    public const string PRODUCT_ERROR = 'Ce produit n\'existe pas.';

    private const string NAME_ERROR = 'Indiquez votre nom (2 caractères minimum).';
    private const string EMAIL_ERROR = 'Indiquez une adresse e-mail valide, par exemple nom@domaine.fr.';
    private const string MESSAGE_ERROR = 'Votre message doit contenir au moins 10 caractères.';
    private const string NAME_MAX_ERROR = 'Votre nom ne doit pas dépasser 255 caractères.';
    private const string EMAIL_MAX_ERROR = 'Votre adresse e-mail ne doit pas dépasser 255 caractères.';
    private const string MESSAGE_MAX_ERROR = 'Votre message ne doit pas dépasser 5000 caractères.';
}
