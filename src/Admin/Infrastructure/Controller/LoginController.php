<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Page de connexion de l'admin (gabarit d'EasyAdmin). Le pare-feu « admin » traite l'envoi du formulaire.
 */
final class LoginController extends AbstractController
{
    // _locale : la page est hors du tableau de bord, elle ne reçoit pas sa langue ; les messages de Security en dépendent
    #[Route('/admin/login', name: 'admin_login', methods: ['GET', 'POST'], defaults: ['_locale' => 'fr'])]
    public function __invoke(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('@EasyAdmin/page/login.html.twig', [
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'last_username' => $authenticationUtils->getLastUsername(),
            'page_title' => 'Administration',
            'csrf_token_intention' => 'authenticate',
            'target_path' => $this->generateUrl('admin'),
            'username_label' => 'Adresse e-mail',
            'password_label' => 'Mot de passe',
            'sign_in_label' => 'Se connecter',
        ]);
    }
}
