<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Controller;

use App\Admin\Infrastructure\EasyAdmin\AdminLocaleSubscriber;
use App\Admin\Infrastructure\EasyAdmin\AdminMessages;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Page de connexion de l'admin (gabarit d'EasyAdmin), dans la langue choisie pour l'interface (AdminLocaleSubscriber).
 * Le pare-feu « admin » traite l'envoi du formulaire.
 */
final class LoginController extends AbstractController
{
    // _locale : la page est hors du tableau de bord, elle ne reçoit pas sa langue ; les messages de Security en dépendent
    #[Route('/admin/login', name: 'admin_login', methods: ['GET', 'POST'], defaults: ['_locale' => AdminLocaleSubscriber::DEFAULT_LOCALE])]
    public function __invoke(AuthenticationUtils $authenticationUtils, TranslatorInterface $translator): Response
    {
        return $this->render('@EasyAdmin/page/login.html.twig', [
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'last_username' => $authenticationUtils->getLastUsername(),
            'page_title' => $translator->trans('dashboard.title', [], AdminMessages::DOMAIN),
            'csrf_token_intention' => 'authenticate',
            'target_path' => $this->generateUrl('admin'),
            'username_label' => $translator->trans('login.email', [], AdminMessages::DOMAIN),
            'password_label' => $translator->trans('login.password', [], AdminMessages::DOMAIN),
            'sign_in_label' => $translator->trans('login.sign_in', [], AdminMessages::DOMAIN),
        ]);
    }
}
