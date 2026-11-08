<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\EasyAdmin;

use App\Admin\Infrastructure\Form\ProductAttributeType;
use App\Catalog\Application\AttributeChoiceData;
use App\Catalog\Application\CreateProductAttribute;
use App\Catalog\Application\DeleteProductAttribute;
use App\Catalog\Application\ProductAttributeData;
use App\Catalog\Application\UpdateProductAttribute;
use App\Catalog\Domain\AttributeChoice;
use App\Catalog\Domain\CatalogRuleViolation;
use App\Catalog\Domain\ProductAttribute;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;

/**
 * Attributs de produit configurables (section 23 de la spec commune).
 * Listes et fiches par EasyAdmin ; création, modification et suppression par les cas d'usage du catalogue (ADR 0001).
 *
 * @extends AbstractCrudController<ProductAttribute>
 */
final class ProductAttributeCrudController extends AbstractCrudController
{
    /** Lignes vides proposées sous les valeurs existantes. */
    private const int EMPTY_CHOICE_ROWS = 3;

    /** Modèle texte imposé aux champs virtuels : sans lui, EasyAdmin les affiche « Inaccessible », avant même formatValue. */
    private const TEXT_TEMPLATE = '@EasyAdmin/crud/field/text.html.twig';

    public function __construct(
        private readonly CreateProductAttribute $createAttribute,
        private readonly UpdateProductAttribute $updateAttribute,
        private readonly DeleteProductAttribute $deleteAttribute,
        private readonly AdminUrlGenerator $urls,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return ProductAttribute::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Attribut')
            ->setEntityLabelInPlural('Attributs')
            ->setDefaultSort(['position' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('label', 'Libellé');
        yield TextField::new('code', 'Code');
        // Champs virtuels formatés depuis l'entité : l'énumération et la liste ne se convertissent pas en texte
        yield TextField::new('typeLabel', 'Type')
            ->setVirtual(true)
            ->setTemplatePath(self::TEXT_TEMPLATE)
            ->formatValue(static fn (mixed $value, ProductAttribute $attribute): string => ProductAttributeType::typeLabel($attribute->getType()));
        yield TextField::new('unitLabel', 'Unité')
            ->setVirtual(true)
            ->setTemplatePath(self::TEXT_TEMPLATE)
            ->formatValue(static fn (mixed $value, ProductAttribute $attribute): string => $attribute->getUnit() ?? '—');
        yield TextField::new('choicesLabel', 'Valeurs')
            ->setVirtual(true)
            ->setTemplatePath(self::TEXT_TEMPLATE)
            ->formatValue(static fn (mixed $value, ProductAttribute $attribute): string => implode(', ', array_map(static fn (AttributeChoice $choice): string => $choice->label, $attribute->getChoices())) ?: '—');
        // renderAsSwitch(false) : l'interrupteur d'EasyAdmin écrirait directement dans l'entité, sans le cas d'usage
        yield BooleanField::new('filterable', 'Filtre du catalogue')->renderAsSwitch(false);
        yield IntegerField::new('position', 'Position');
    }

    public function new(AdminContext $context): Response
    {
        return $this->form($context, new ProductAttributeData(), null, 'Nouvel attribut');
    }

    public function edit(AdminContext $context): Response
    {
        $attribute = $context->getEntity()->getInstance();
        \assert($attribute instanceof ProductAttribute);

        return $this->form($context, ProductAttributeData::fromAttribute($attribute), $attribute->getId(), \sprintf('Modifier « %s »', $attribute->getLabel()));
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        try {
            ($this->deleteAttribute)((int) $entityInstance->getId());
        } catch (CatalogRuleViolation $violation) {
            $this->addFlash('danger', $violation->getMessage());
        }
    }

    /**
     * @param AdminContext<ProductAttribute> $context
     */
    private function form(AdminContext $context, ProductAttributeData $data, ?int $id, string $title): Response
    {
        for ($row = 0; $row < self::EMPTY_CHOICE_ROWS; ++$row) {
            $data->choices[] = new AttributeChoiceData();
        }
        $form = $this->createForm(ProductAttributeType::class, $data, ['code_locked' => null !== $id]);
        $form->handleRequest($context->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                null === $id ? ($this->createAttribute)($data) : ($this->updateAttribute)($id, $data);
                $this->addFlash('success', \sprintf('Attribut « %s » enregistré.', $data->label));

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
