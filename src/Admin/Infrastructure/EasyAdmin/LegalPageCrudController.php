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
        private readonly AdminMessages $messages,
        private readonly FormLocales $formLocales,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return LegalPage::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('entity.legal_page')
            ->setEntityLabelInPlural('entity.legal_pages')
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
        yield TextField::new('title', 'field.title');
        yield TextField::new('slug', 'field.slug');
        yield TextField::new('body', 'field.markdown_body')->onlyOnDetail();
    }

    public function edit(AdminContext $context): Response
    {
        $page = $context->getEntity()->getInstance();
        \assert($page instanceof LegalPage);

        $data = LegalPageData::fromLegalPage($page);
        $form = $this->createForm(LegalPageType::class, $data, $this->formLocales->options());
        $form->handleRequest($context->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                ($this->updateLegalPage)((int) $page->getId(), $data);
                $this->addFlash('success', $this->messages->trans('flash.page_saved', ['%name%' => $data->title]));

                return $this->redirect($this->urls->setController(self::class)->setAction(Action::INDEX)->generateUrl());
            } catch (ContentRuleViolation $violation) {
                $form->addError(new FormError($this->messages->violation($violation)));
            }
        }

        return $this->render('admin/form.html.twig', [
            'form' => $form,
            'title' => $this->messages->trans('title.edit', ['%name%' => $page->getTitle()]),
            'cancel_url' => $this->urls->setController(self::class)->setAction(Action::INDEX)->generateUrl(),
        ]);
    }
}
