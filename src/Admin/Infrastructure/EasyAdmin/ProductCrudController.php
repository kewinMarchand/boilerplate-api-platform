<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\EasyAdmin;

use App\Admin\Infrastructure\Form\ProductType;
use App\Catalog\Application\CreateProduct;
use App\Catalog\Application\DeleteProduct;
use App\Catalog\Application\ProductData;
use App\Catalog\Application\UpdateProduct;
use App\Catalog\Domain\CatalogRuleViolation;
use App\Catalog\Domain\CategoryRepository;
use App\Catalog\Domain\Product;
use App\Media\Domain\Image;
use App\Media\Domain\ImageUsage;
use App\Media\Domain\MediaRuleViolation;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;

/**
 * Listes et fiches par EasyAdmin ; création, modification et suppression par les cas d'usage du catalogue (ADR 0001).
 *
 * @extends AbstractCrudController<Product>
 */
final class ProductCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly CreateProduct $createProduct,
        private readonly UpdateProduct $updateProduct,
        private readonly DeleteProduct $deleteProduct,
        private readonly AdminUrlGenerator $urls,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Produit')
            ->setEntityLabelInPlural('Produits')
            ->setDefaultSort(['name' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield ImagePreview::column(ImageUsage::Product, static fn (object $product): ?Image => $product instanceof Product ? $product->getImage() : null);
        yield TextField::new('name', 'Nom');
        yield TextField::new('slug', 'Slug');
        // Champs virtuels formatés depuis l'entité : sur une colonne liée, EasyAdmin devinerait un type et perdrait la mise en forme
        yield TextField::new('categoryName', 'Catégorie')
            ->setVirtual(true)
            ->formatValue(static fn (mixed $value, Product $product): string => $product->getCategory()->getName());
        yield TextField::new('priceLabel', 'Prix')
            ->setVirtual(true)
            ->formatValue(static fn (mixed $value, Product $product): string => number_format($product->getPrice()->cents / 100, 2, ',', ' ').' €');
        yield TextField::new('exposureLabel', 'Exposition')
            ->setVirtual(true)
            ->formatValue(static fn (mixed $value, Product $product): string => $product->getExposure()->value);
        yield TextField::new('sizeLabel', 'Taille')
            ->setVirtual(true)
            ->formatValue(static fn (mixed $value, Product $product): string => $product->getSize()->value);
        // renderAsSwitch(false) : l'interrupteur d'EasyAdmin écrirait directement dans l'entité, sans le cas d'usage
        yield BooleanField::new('inStock', 'En stock')->renderAsSwitch(false);
        yield TextField::new('imageAlt', 'Texte de remplacement')->hideOnIndex();
    }

    public function new(AdminContext $context): Response
    {
        return $this->form($context, new ProductData(), null, 'Nouveau produit');
    }

    public function edit(AdminContext $context): Response
    {
        $product = $context->getEntity()->getInstance();
        \assert($product instanceof Product);

        return $this->form($context, ProductData::fromProduct($product), $product->getId(), \sprintf('Modifier « %s »', $product->getName()), $product->getImage());
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        try {
            ($this->deleteProduct)((int) $entityInstance->getId());
        } catch (CatalogRuleViolation $violation) {
            $this->addFlash('danger', $violation->getMessage());
        }
    }

    /**
     * @param AdminContext<Product> $context
     */
    private function form(AdminContext $context, ProductData $data, ?int $id, string $title, ?Image $image = null): Response
    {
        $categories = [];
        foreach ($this->categories->findAll() as $category) {
            $categories[str_repeat('— ', $category->depth() - 1).$category->getName()] = $category->getId();
        }
        $form = $this->createForm(ProductType::class, $data, ['categories' => $categories]);
        $form->handleRequest($context->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                null === $id ? ($this->createProduct)($data) : ($this->updateProduct)($id, $data);
                $this->addFlash('success', \sprintf('Produit « %s » enregistré.', $data->name));

                return $this->redirect($this->urls->setController(self::class)->setAction(Action::INDEX)->generateUrl());
            } catch (CatalogRuleViolation|MediaRuleViolation|\InvalidArgumentException $violation) {
                $form->addError(new FormError($violation->getMessage()));
            }
        }

        return $this->render('admin/form.html.twig', [
            'form' => $form,
            'title' => $title,
            'image_preview' => ImagePreview::url($image, ImageUsage::Product),
            'cancel_url' => $this->urls->setController(self::class)->setAction(Action::INDEX)->generateUrl(),
        ]);
    }
}
