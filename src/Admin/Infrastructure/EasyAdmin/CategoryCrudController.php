<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\EasyAdmin;

use App\Admin\Infrastructure\Form\CategoryType;
use App\Catalog\Application\CategoryData;
use App\Catalog\Application\CreateCategory;
use App\Catalog\Application\DeleteCategory;
use App\Catalog\Application\UpdateCategory;
use App\Catalog\Domain\CatalogRuleViolation;
use App\Catalog\Domain\Category;
use App\Catalog\Domain\CategoryRepository;
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
 * Listes et fiches par EasyAdmin ; création, modification et suppression par les cas d'usage du catalogue (ADR 0001).
 *
 * @extends AbstractCrudController<Category>
 */
final class CategoryCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly CreateCategory $createCategory,
        private readonly UpdateCategory $updateCategory,
        private readonly DeleteCategory $deleteCategory,
        private readonly AdminUrlGenerator $urls,
    ) {
    }

    /** Modèle texte imposé aux champs virtuels : sans lui, EasyAdmin les affiche « Inaccessible », avant même formatValue. */
    private const TEXT_TEMPLATE = '@EasyAdmin/crud/field/text.html.twig';

    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Catégorie')
            ->setEntityLabelInPlural('Catégories')
            ->setDefaultSort(['position' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Nom');
        yield TextField::new('slug', 'Slug');
        yield IntegerField::new('position', 'Position');
        // Champ virtuel formaté depuis l'entité : sur une colonne liée, EasyAdmin devinerait un type et perdrait la mise en forme
        yield TextField::new('parentName', 'Parent')
            ->setVirtual(true)
            ->setTemplatePath(self::TEXT_TEMPLATE)
            ->formatValue(static fn (mixed $value, Category $category): string => $category->getParent()?->getName() ?? '—');
    }

    public function new(AdminContext $context): Response
    {
        return $this->form($context, new CategoryData(), null, 'Nouvelle catégorie');
    }

    public function edit(AdminContext $context): Response
    {
        $category = $context->getEntity()->getInstance();
        \assert($category instanceof Category);

        return $this->form($context, CategoryData::fromCategory($category), $category->getId(), \sprintf('Modifier « %s »', $category->getName()));
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        try {
            ($this->deleteCategory)((int) $entityInstance->getId());
        } catch (CatalogRuleViolation $violation) {
            $this->addFlash('danger', $violation->getMessage());
        }
    }

    /**
     * @param AdminContext<Category> $context
     */
    private function form(AdminContext $context, CategoryData $data, ?int $id, string $title): Response
    {
        $parents = [];
        foreach ($this->categories->findAll() as $category) {
            if ($category->getId() !== $id) {
                $parents[str_repeat('— ', $category->depth() - 1).$category->getName()] = $category->getId();
            }
        }
        $form = $this->createForm(CategoryType::class, $data, ['parents' => $parents]);
        $form->handleRequest($context->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                null === $id ? ($this->createCategory)($data) : ($this->updateCategory)($id, $data);
                $this->addFlash('success', \sprintf('Catégorie « %s » enregistrée.', $data->name));

                return $this->redirect($this->urls->setController(self::class)->setAction(Action::INDEX)->generateUrl());
            } catch (CatalogRuleViolation $violation) {
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
