<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\EasyAdmin;

use App\Admin\Infrastructure\Form\ProductType;
use App\Catalog\Application\CreateProduct;
use App\Catalog\Application\DeleteProduct;
use App\Catalog\Application\GalleryImageData;
use App\Catalog\Application\ProductData;
use App\Catalog\Application\UpdateProduct;
use App\Catalog\Domain\CatalogRuleViolation;
use App\Catalog\Domain\CategoryRepository;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductAttributeRepository;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
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
        private readonly ProductAttributeRepository $attributes,
        private readonly CreateProduct $createProduct,
        private readonly UpdateProduct $updateProduct,
        private readonly DeleteProduct $deleteProduct,
        private readonly AdminUrlGenerator $urls,
        private readonly AdminMessages $messages,
        private readonly FormLocales $formLocales,
    ) {
    }

    /** Lignes d'ajout proposées sous les images de la galerie. */
    private const int EMPTY_GALLERY_ROWS = 2;

    /** Modèle texte imposé aux champs virtuels : sans lui, EasyAdmin les affiche « Inaccessible », avant même formatValue. */
    private const TEXT_TEMPLATE = '@EasyAdmin/crud/field/text.html.twig';

    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('entity.product')
            ->setEntityLabelInPlural('entity.products')
            ->setDefaultSort(['name' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield ImagePreview::column(ImageUsage::Product, static fn (object $product): ?Image => $product instanceof Product ? $product->getImage() : null);
        yield TextField::new('name', 'field.name');
        yield TextField::new('slug', 'field.slug');
        // Champs virtuels formatés depuis l'entité : sur une colonne liée, EasyAdmin devinerait un type et perdrait la mise en forme
        yield TextField::new('categoryName', 'field.category')
            ->setVirtual(true)
            ->setTemplatePath(self::TEXT_TEMPLATE)
            ->formatValue(static fn (mixed $value, Product $product): string => $product->getCategory()->getName());
        yield TextField::new('priceLabel', 'field.price')
            ->setVirtual(true)
            ->setTemplatePath(self::TEXT_TEMPLATE)
            ->formatValue(static fn (mixed $value, Product $product): string => number_format($product->getPrice()->cents / 100, 2, ',', ' ').' €');
        yield TextField::new('exposureLabel', 'field.exposure')
            ->setVirtual(true)
            ->setTemplatePath(self::TEXT_TEMPLATE)
            ->formatValue(fn (mixed $value, Product $product): string => $this->messages->trans('exposure.'.$product->getExposure()->value));
        yield TextField::new('sizeLabel', 'field.size')
            ->setVirtual(true)
            ->setTemplatePath(self::TEXT_TEMPLATE)
            ->formatValue(static fn (mixed $value, Product $product): string => $product->getSize()->value);
        // renderAsSwitch(false) : l'interrupteur d'EasyAdmin écrirait directement dans l'entité, sans le cas d'usage
        yield BooleanField::new('inStock', 'field.in_stock')->renderAsSwitch(false);
        yield BooleanField::new('featured', 'field.featured')->renderAsSwitch(false);
        yield TextareaField::new('description', 'field.description')->onlyOnDetail();
        yield TextField::new('imageAlt', 'field.alt')->hideOnIndex();
    }

    public function new(AdminContext $context): Response
    {
        return $this->form($context, new ProductData(), null, $this->messages->trans('title.new_product'));
    }

    public function edit(AdminContext $context): Response
    {
        $product = $context->getEntity()->getInstance();
        \assert($product instanceof Product);

        return $this->form($context, ProductData::fromProduct($product), $product->getId(), $this->messages->trans('title.edit', ['%name%' => $product->getName()]), $product->getImage());
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        try {
            ($this->deleteProduct)((int) $entityInstance->getId());
        } catch (CatalogRuleViolation $violation) {
            $this->addFlash('danger', $this->messages->violation($violation));
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
        for ($row = 0; $row < self::EMPTY_GALLERY_ROWS; ++$row) {
            $empty = new GalleryImageData();
            $empty->position = \count($data->gallery) + 1;
            $data->gallery[] = $empty;
        }
        $form = $this->createForm(ProductType::class, $data, ['categories' => $categories, 'attributes' => $this->attributes->findAll(), ...$this->formLocales->options()]);
        $form->handleRequest($context->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                null === $id ? ($this->createProduct)($data) : ($this->updateProduct)($id, $data);
                $this->addFlash('success', $this->messages->trans('flash.product_saved', ['%name%' => $data->name]));

                return $this->redirect($this->urls->setController(self::class)->setAction(Action::INDEX)->generateUrl());
            } catch (CatalogRuleViolation|MediaRuleViolation|\InvalidArgumentException $violation) {
                $form->addError(new FormError($this->messages->violation($violation)));
            }
        }

        return $this->render('admin/form.html.twig', [
            'form' => $form,
            'title' => $title,
            'image_previews' => ['imageContent' => ImagePreview::figure($image, ImageUsage::Product)],
            'cancel_url' => $this->urls->setController(self::class)->setAction(Action::INDEX)->generateUrl(),
        ]);
    }
}
