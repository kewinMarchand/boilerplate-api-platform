<?php

declare(strict_types=1);

namespace App\Contact\Application;

use App\Contact\Domain\ContactMail;
use App\Contact\Domain\ContactMailer;
use App\Contact\Domain\ContactMessage;
use App\Contact\Domain\ContactMessageRepository;
use App\Contact\Domain\ProductCatalog;
use App\Contact\Domain\UnknownProduct;

/**
 * Enregistre le message, puis l'envoie par mail (SPEC-COMMUNE, section 27).
 */
final readonly class SendContactMessage
{
    public function __construct(
        private ContactMessageRepository $messages,
        private ProductCatalog $products,
        private ContactMailer $mailer,
    ) {
    }

    /**
     * @param string|null $productSlug produit d'une demande de renseignement, null pour un message de contact simple
     *
     * @throws UnknownProduct
     */
    public function __invoke(string $name, string $email, string $message, ?string $productSlug): ContactMessage
    {
        $product = null === $productSlug ? null : ($this->products->findBySlug($productSlug) ?? throw new UnknownProduct($productSlug));

        $contactMessage = new ContactMessage($name, $email, $message, $product?->slug, new \DateTimeImmutable());
        $this->messages->save($contactMessage);
        $this->mailer->send(ContactMail::for($contactMessage, $product));

        return $contactMessage;
    }
}
