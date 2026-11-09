<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Form;

use App\Admin\Infrastructure\EasyAdmin\AdminMessages;
use App\Catalog\Application\ProductData;
use App\Catalog\Domain\AttributeChoice;
use App\Catalog\Domain\AttributeType;
use App\Catalog\Domain\Exposure;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductAttribute;
use App\Catalog\Domain\Size;
use App\Media\Domain\ImageUsage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Validation de forme à la frontière ; les règles du catalogue sont vérifiées par les cas d'usage.
 *
 * @extends AbstractType<ProductData>
 */
final class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'field.name', 'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255)]])
            ->add('slug', TextType::class, [
                'label' => 'field.slug',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255), new Assert\Regex(CategoryType::SLUG_PATTERN, 'constraint.slug')],
            ])
            ->add('categoryId', ChoiceType::class, [
                'label' => 'field.category',
                'choices' => $options['categories'],
                'choice_translation_domain' => false,
                'placeholder' => 'product.choose_category',
                'constraints' => [new Assert\NotNull(message: 'constraint.category')],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'field.price',
                'currency' => 'EUR',
                'divisor' => 100,
                'input' => 'integer',
                'constraints' => [new Assert\PositiveOrZero(message: 'constraint.price')],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'field.description',
                'help' => 'help.product_description',
                'required' => false,
                'empty_data' => '',
                'attr' => ['rows' => 6],
                'constraints' => [new Assert\Length(max: 2000)],
            ])
            ->add('exposure', EnumType::class, [
                'label' => 'field.exposure',
                'class' => Exposure::class,
                'choice_label' => static fn (Exposure $exposure): string => 'exposure.'.$exposure->value,
            ])
            ->add('size', EnumType::class, [
                'label' => 'field.size',
                'class' => Size::class,
                'choice_label' => static fn (Size $size): string => $size->value,
                'choice_translation_domain' => false,
            ])
            ->add('inStock', CheckboxType::class, ['label' => 'field.in_stock', 'required' => false])
            ->add('featured', CheckboxType::class, ['label' => 'field.featured', 'help' => 'help.featured', 'required' => false]);
        ImageFields::add($builder, ImageUsage::Product, 'help.product_alt');
        $builder->add('gallery', CollectionType::class, [
            'label' => 'field.gallery',
            'help' => 'help.gallery',
            'help_translation_parameters' => ['%max%' => Product::MAX_GALLERY_IMAGES],
            'entry_type' => GalleryImageType::class,
            'allow_add' => true,
            'allow_delete' => true,
        ]);
        MetaFields::add($builder);
        /** @var list<ProductAttribute> $attributes */
        $attributes = $options['attributes'];
        $this->addAttributeFields($builder, $attributes);
    }

    /**
     * Un champ facultatif par attribut défini dans la section « Attributs ».
     *
     * @template TData
     *
     * @param FormBuilderInterface<TData> $builder
     * @param list<ProductAttribute>      $attributes
     */
    private function addAttributeFields(FormBuilderInterface $builder, array $attributes): void
    {
        if ([] === $attributes) {
            return;
        }
        $fields = $builder->create('attributes', FormType::class, ['label' => 'field.attributes', 'required' => false]);
        foreach ($attributes as $attribute) {
            $label = null === $attribute->getUnit() ? $attribute->getLabel() : \sprintf('%s (%s)', $attribute->getLabel(), $attribute->getUnit());
            // Libellés saisis dans l'admin : du contenu, pas des clés de traduction. La liste garde le domaine pour son
            // placeholder ; son libellé, qu'aucune clé ne nomme, s'affiche tel quel.
            match ($attribute->getType()) {
                AttributeType::Choice => $fields->add($attribute->getCode(), ChoiceType::class, [
                    'label' => $label,
                    'required' => false,
                    'placeholder' => 'product.attribute_unset',
                    'choice_translation_domain' => false,
                    'choices' => array_column(array_map(static fn (AttributeChoice $choice): array => [$choice->label, $choice->value], $attribute->getChoices()), 1, 0),
                ]),
                AttributeType::Number => $fields->add($attribute->getCode(), NumberType::class, [
                    'label' => $label,
                    'translation_domain' => false,
                    'required' => false,
                    'input' => 'string',
                    'html5' => true,
                    'attr' => ['step' => 'any'],
                ]),
                AttributeType::Text => $fields->add($attribute->getCode(), TextType::class, [
                    'label' => $label,
                    'translation_domain' => false,
                    'required' => false,
                    'empty_data' => '',
                    'constraints' => [new Assert\Length(max: ProductAttribute::TEXT_MAX_LENGTH)],
                ]),
            };
        }
        $builder->add($fields);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['translation_domain' => AdminMessages::DOMAIN, 'data_class' => ProductData::class, 'categories' => [], 'attributes' => []]);
        $resolver->setAllowedTypes('categories', 'array');
        $resolver->setAllowedTypes('attributes', 'array');
    }
}
