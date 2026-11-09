<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\EasyAdmin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin', routeOptions: ['defaults' => ['_locale' => 'fr']])]
final class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly AdminUrlGenerator $urls,
        private readonly AdminMessages $messages,
    ) {
    }

    public function index(): Response
    {
        return $this->redirect($this->urls->setController(ProductCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        // Langues de l'interface, chacune écrite dans sa langue ; le choix est gardé en session (AdminLocaleSubscriber)
        return Dashboard::new()
            ->setTitle($this->messages->trans('dashboard.title'))
            ->setTranslationDomain(AdminMessages::DOMAIN)
            ->setLocales(AdminLocaleSubscriber::LOCALES);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::section('menu.catalog');
        yield MenuItem::linkTo(CategoryCrudController::class, 'menu.categories', 'fa fa-folder-tree');
        yield MenuItem::linkTo(ProductCrudController::class, 'menu.products', 'fa fa-seedling');
        yield MenuItem::linkTo(ProductAttributeCrudController::class, 'menu.attributes', 'fa fa-tags');
        yield MenuItem::section('menu.contents');
        yield MenuItem::linkTo(ArticleCrudController::class, 'menu.articles', 'fa fa-newspaper');
        yield MenuItem::linkTo(ArticleSectionCrudController::class, 'menu.sections', 'fa fa-folder-open');
        yield MenuItem::linkTo(ArticleFormatCrudController::class, 'menu.formats', 'fa fa-shapes');
        yield MenuItem::linkTo(LegalPageCrudController::class, 'menu.legal_pages', 'fa fa-scale-balanced');
        yield MenuItem::section('menu.site');
        yield MenuItem::linkTo(SiteIdentityCrudController::class, 'menu.site_identity', 'fa fa-palette');
        yield MenuItem::linkTo(PageMetaCrudController::class, 'menu.page_metas', 'fa fa-magnifying-glass');
        yield MenuItem::section('menu.contact');
        yield MenuItem::linkTo(ContactMessageCrudController::class, 'menu.messages', 'fa fa-envelope');
    }
}
