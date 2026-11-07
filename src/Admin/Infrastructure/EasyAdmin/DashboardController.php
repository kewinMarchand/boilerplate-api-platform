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
    public function __construct(private readonly AdminUrlGenerator $urls)
    {
    }

    public function index(): Response
    {
        return $this->redirect($this->urls->setController(ProductCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Administration')->setLocales(['fr']);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::section('Catalogue');
        yield MenuItem::linkTo(CategoryCrudController::class, 'Catégories', 'fa fa-folder-tree');
        yield MenuItem::linkTo(ProductCrudController::class, 'Produits', 'fa fa-seedling');
        yield MenuItem::section('Contenus');
        yield MenuItem::linkTo(ArticleCrudController::class, 'Articles', 'fa fa-newspaper');
        yield MenuItem::linkTo(LegalPageCrudController::class, 'Pages légales', 'fa fa-scale-balanced');
    }
}
