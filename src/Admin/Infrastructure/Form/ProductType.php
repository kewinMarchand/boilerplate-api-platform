<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Form;

use App\Catalog\Application\ProductData;
use App\Catalog\Domain\Exposure;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\Size;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
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
            ->add('name', TextType::class, ['label' => 'Nom', 'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255)]])
            ->add('slug', TextType::class, [
                'label' => 'Slug',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255), new Assert\Regex(CategoryType::SLUG_PATTERN, 'Minuscules, chiffres et tirets seulement.')],
            ])
            ->add('categoryId', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => $options['categories'],
                'placeholder' => 'Choisir une catégorie',
                'constraints' => [new Assert\NotNull(message: 'Choisir une catégorie.')],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix',
                'currency' => 'EUR',
                'divisor' => 100,
                'input' => 'integer',
                'constraints' => [new Assert\PositiveOrZero(message: 'Le prix ne peut pas être négatif.')],
            ])
            ->add('exposure', EnumType::class, [
                'label' => 'Exposition',
                'class' => Exposure::class,
                'choice_label' => static fn (Exposure $exposure): string => $exposure->value,
            ])
            ->add('size', EnumType::class, [
                'label' => 'Taille',
                'class' => Size::class,
                'choice_label' => static fn (Size $size): string => $size->value,
            ])
            ->add('inStock', CheckboxType::class, ['label' => 'En stock', 'required' => false])
            ->add('image', IntegerType::class, [
                'label' => 'Visuel',
                'help' => \sprintf('Numéro du visuel partagé, de 1 à %d.', Product::IMAGE_COUNT),
                'constraints' => [new Assert\Range(min: 1, max: Product::IMAGE_COUNT)],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ProductData::class, 'categories' => []]);
        $resolver->setAllowedTypes('categories', 'array');
    }
}
