<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Form;

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
            ->add('name', TextType::class, ['label' => 'Nom', 'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255)]])
            ->add('slug', TextType::class, [
                'label' => 'Slug',
                'help' => 'Minuscules, chiffres et tirets : il apparaît dans les adresses du site (?category=…).',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255), new Assert\Regex(self::SLUG_PATTERN, 'Minuscules, chiffres et tirets seulement.')],
            ])
            ->add('position', IntegerType::class, ['label' => 'Position', 'constraints' => [new Assert\PositiveOrZero()]])
            ->add('parentId', ChoiceType::class, [
                'label' => 'Parent',
                'choices' => $options['parents'],
                'required' => false,
                'placeholder' => 'Aucun (catégorie racine)',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => CategoryData::class, 'parents' => []]);
        $resolver->setAllowedTypes('parents', 'array');
    }
}
