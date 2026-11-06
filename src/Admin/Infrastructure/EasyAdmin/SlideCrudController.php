<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\EasyAdmin;

use App\Admin\Infrastructure\Form\SlideType;
use App\Content\Application\CreateSlide;
use App\Content\Application\DeleteSlide;
use App\Content\Application\SlideData;
use App\Content\Application\UpdateSlide;
use App\Content\Domain\ContentRuleViolation;
use App\Content\Domain\Slide;
use App\Media\Domain\MediaRuleViolation;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;

/**
 * Listes et fiches par EasyAdmin ; création, modification et suppression par les cas d'usage des contenus (ADR 0001).
 *
 * @extends AbstractCrudController<Slide>
 */
final class SlideCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly CreateSlide $createSlide,
        private readonly UpdateSlide $updateSlide,
        private readonly DeleteSlide $deleteSlide,
        private readonly AdminUrlGenerator $urls,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Slide::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Diapositive')
            ->setEntityLabelInPlural('Carrousel')
            ->setDefaultSort(['position' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IntegerField::new('position', 'Position');
        yield TextField::new('title', 'Titre');
        yield TextField::new('slug', 'Slug');
        yield TextField::new('imageAlt', 'Texte de remplacement')->hideOnIndex();
        yield TextField::new('text', 'Texte')->hideOnIndex();
    }

    public function new(AdminContext $context): Response
    {
        return $this->form($context, new SlideData(), null, 'Nouvelle diapositive');
    }

    public function edit(AdminContext $context): Response
    {
        $slide = $context->getEntity()->getInstance();
        \assert($slide instanceof Slide);

        return $this->form($context, SlideData::fromSlide($slide), $slide->getId(), \sprintf('Modifier « %s »', $slide->getTitle()));
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        try {
            ($this->deleteSlide)((int) $entityInstance->getId());
        } catch (ContentRuleViolation $violation) {
            $this->addFlash('danger', $violation->getMessage());
        }
    }

    /**
     * @param AdminContext<Slide> $context
     */
    private function form(AdminContext $context, SlideData $data, ?int $id, string $title): Response
    {
        $form = $this->createForm(SlideType::class, $data);
        $form->handleRequest($context->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                null === $id ? ($this->createSlide)($data) : ($this->updateSlide)($id, $data);
                $this->addFlash('success', \sprintf('Diapositive « %s » enregistrée.', $data->title));

                return $this->redirect($this->urls->setController(self::class)->setAction(Action::INDEX)->generateUrl());
            } catch (ContentRuleViolation|MediaRuleViolation $violation) {
                $form->addError(new FormError($violation->getMessage()));
            }
        }

        return $this->render('admin/form.html.twig', [
            'form' => $form,
            'title' => $title,
            'cancel_url' => $this->urls->setController(self::class)->setAction(Action::INDEX)->generateUrl(),
        ]);
    }
}
