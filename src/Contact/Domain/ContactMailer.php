<?php

declare(strict_types=1);

namespace App\Contact\Domain;

interface ContactMailer
{
    /**
     * Envoie le mail à l'adresse qui reçoit les messages. Ne lève rien : le message est déjà enregistré, un échec d'envoi
     * est journalisé et le visiteur n'a pas à réessayer.
     */
    public function send(ContactMail $mail): void;
}
