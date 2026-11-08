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
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return PageMeta::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Référencement')
            ->setEntityLabelInPlural('Référencement')
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
        yield TextField::new('pageLabel', 'Page')
            ->setVirtual(true)
            ->setTemplatePath(self::TEXT_TEMPLATE)
            ->formatValue(static fn (mixed $value, PageMeta $meta): string => $meta->getPage()->label());
        yield TextField::new('title', 'Titre');
        yield TextField::new('description', 'Description');
    }

    public function edit(AdminContext $context): Response
    {
        $meta = $context->getEntity()->getInstance();
        \assert($meta instanceof PageMeta);

        $data = PageMetaData::fromPageMeta($meta);
        $form = $this->createForm(PageMetaType::class, $data);
        $form->handleRequest($context->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                ($this->updatePageMeta)((int) $meta->getId(), $data);
                $this->addFlash('success', \sprintf('Référencement de « %s » enregistré.', $meta->getPage()->label()));

                return $this->redirect($this->urls->setController(self::class)->setAction(Action::INDEX)->generateUrl());
            } catch (ContentRuleViolation $violation) {
                $form->addError(new FormError($violation->getMessage()));
            }
        }

        return $this->render('admin/form.html.twig', [
            'form' => $form,
            'title' => \sprintf('Référencement de « %s »', $meta->getPage()->label()),
            'cancel_url' => $this->urls->setController(self::class)->setAction(Action::INDEX)->generateUrl(),
        ]);
    }
}
