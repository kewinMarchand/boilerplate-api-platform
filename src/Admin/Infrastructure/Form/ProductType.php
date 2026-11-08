<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Form;

use App\Catalog\Application\ProductData;
use App\Catalog\Domain\Exposure;
use App\Catalog\Domain\Size;
use App\Media\Domain\ImageUsage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
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
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'help' => 'Texte brut, affiché sur la fiche du produit. Une ligne vide sépare deux paragraphes.',
                'required' => false,
                'empty_data' => '',
                'attr' => ['rows' => 6],
                'constraints' => [new Assert\Length(max: 2000)],
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
            ->add('featured', CheckboxType::class, ['label' => 'À la une', 'help' => 'Montré dans le carrousel de la page d\'accueil, par ordre alphabétique.', 'required' => false]);
        ImageFields::add($builder, ImageUsage::Product, 'Vide : le nom du produit, qui suffit le plus souvent.');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ProductData::class, 'categories' => []]);
        $resolver->setAllowedTypes('categories', 'array');
    }
}
