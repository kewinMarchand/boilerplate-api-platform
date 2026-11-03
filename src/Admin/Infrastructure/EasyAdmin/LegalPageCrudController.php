<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\EasyAdmin;

use App\Admin\Infrastructure\Form\LegalPageType;
use App\Content\Application\LegalPageData;
use App\Content\Application\UpdateLegalPage;
use App\Content\Domain\ContentRuleViolation;
use App\Content\Domain\LegalPage;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pages fixes, liées aux routes des fronts : modification seule, par le cas d'usage (ADR 0001).
 *
 * @extends AbstractCrudController<LegalPage>
 */
final class LegalPageCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UpdateLegalPage $updateLegalPage,
        private readonly AdminUrlGenerator $urls,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return LegalPage::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Page légale')
            ->setEntityLabelInPlural('Pages légales')
            ->setDefaultSort(['title' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title', 'Titre');
        yield TextField::new('slug', 'Slug');
        yield TextField::new('body', 'Contenu (Markdown)')->onlyOnDetail();
    }

    public function edit(AdminContext $context): Response
    {
        $page = $context->getEntity()->getInstance();
        \assert($page instanceof LegalPage);

        $data = LegalPageData::fromLegalPage($page);
        $form = $this->createForm(LegalPageType::class, $data);
        $form->handleRequest($context->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                ($this->updateLegalPage)((int) $page->getId(), $data);
                $this->addFlash('success', \sprintf('Page « %s » enregistrée.', $data->title));

                return $this->redirect($this->urls->setController(self::class)->setAction(Action::INDEX)->generateUrl());
            } catch (ContentRuleViolation $violation) {
                $form->addError(new FormError($violation->getMessage()));
            }
        }

        return $this->render('admin/form.html.twig', [
            'form' => $form,
            'title' => \sprintf('Modifier « %s »', $page->getTitle()),
            'cancel_url' => $this->urls->setController(self::class)->setAction(Action::INDEX)->generateUrl(),
        ]);
    }
}
