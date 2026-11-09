<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Form;

use App\Admin\Infrastructure\EasyAdmin\AdminMessages;
use App\Catalog\Application\CategoryData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Validation de forme à la frontière ; les règles du catalogue sont vérifiées par les cas d'usage.
 *
 * @extends AbstractType<CategoryData>
 */
final class CategoryType extends AbstractType
{
    public const string SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'field.name', 'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255)]])
            ->add('slug', TextType::class, [
                'label' => 'field.slug',
                'help' => 'help.category_slug',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255), new Assert\Regex(self::SLUG_PATTERN, 'constraint.slug')],
            ])
            ->add('position', IntegerType::class, ['label' => 'field.position', 'constraints' => [new Assert\PositiveOrZero()]])
            ->add('parentId', ChoiceType::class, [
                'label' => 'field.parent',
                'choices' => $options['parents'],
                'choice_translation_domain' => false,
                'required' => false,
                'placeholder' => 'category.no_parent',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['translation_domain' => AdminMessages::DOMAIN, 'data_class' => CategoryData::class, 'parents' => []]);
        $resolver->setAllowedTypes('parents', 'array');
    }
}
