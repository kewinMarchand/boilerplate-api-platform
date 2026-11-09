<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\EasyAdmin;

use App\Admin\Infrastructure\Form\PageMetaType;
use App\Content\Application\PageMetaData;
use App\Content\Application\UpdatePageMeta;
use App\Content\Domain\ContentRuleViolation;
use App\Content\Domain\PageMeta;
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
 * Référencement des pages fixes (ADR 0011) : une fiche par page, en modification seule, par le cas d'usage.
 *
 * @extends AbstractCrudController<PageMeta>
 */
final class PageMetaCrudController extends AbstractCrudController
{
    /** Modèle texte imposé aux champs virtuels : sans lui, EasyAdmin les affiche « Inaccessible », avant même formatValue. */
    private const TEXT_TEMPLATE = '@EasyAdmin/crud/field/text.html.twig';

    public function __construct(
        private readonly UpdatePageMeta $updatePageMeta,
        private readonly AdminUrlGenerator $urls,
        private readonly AdminMessages $messages,
        private readonly FormLocales $formLocales,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return PageMeta::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('entity.page_meta')
            ->setEntityLabelInPlural('entity.page_metas')
            ->setDefaultSort(['id' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        // Champ virtuel : l'énumération ne se convertit pas en texte
        yield TextField::new('pageLabel', 'field.page')
            ->setVirtual(true)
            ->setTemplatePath(self::TEXT_TEMPLATE)
            ->formatValue(fn (mixed $value, PageMeta $meta): string => $this->pageLabel($meta));
        yield TextField::new('title', 'field.title');
        yield TextField::new('description', 'field.description');
    }

    public function edit(AdminContext $context): Response
    {
        $meta = $context->getEntity()->getInstance();
        \assert($meta instanceof PageMeta);

        $data = PageMetaData::fromPageMeta($meta);
        $form = $this->createForm(PageMetaType::class, $data, $this->formLocales->options());
        $form->handleRequest($context->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                ($this->updatePageMeta)((int) $meta->getId(), $data);
                $this->addFlash('success', $this->messages->trans('flash.page_meta_saved', ['%name%' => $this->pageLabel($meta)]));

                return $this->redirect($this->urls->setController(self::class)->setAction(Action::INDEX)->generateUrl());
            } catch (ContentRuleViolation $violation) {
                $form->addError(new FormError($this->messages->violation($violation)));
            }
        }

        return $this->render('admin/form.html.twig', [
            'form' => $form,
            'title' => $this->messages->trans('title.page_meta', ['%name%' => $this->pageLabel($meta)]),
            'cancel_url' => $this->urls->setController(self::class)->setAction(Action::INDEX)->generateUrl(),
        ]);
    }

    private function pageLabel(PageMeta $meta): string
    {
        return $this->messages->trans('meta_page.'.$meta->getPage()->value);
    }
}
