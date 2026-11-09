<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Form;

use App\Catalog\Application\AttributeChoiceData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Une ligne de valeur ; la ligne laissée vide est ignorée par le cas d'usage.
 *
 * @extends AbstractType<AttributeChoiceData>
 */
final class AttributeChoiceType extends AbstractType
{
    public const string VALUE_PATTERN = '/^[a-z0-9][a-z0-9-]{0,39}$/';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('value', TextType::class, [
                'label' => 'field.code',
                'required' => false,
                'empty_data' => '',
                'constraints' => [new Assert\Regex(self::VALUE_PATTERN, 'constraint.choice_value')],
            ])
            ->add('label', TextType::class, [
                'label' => 'field.label',
                'required' => false,
                'empty_data' => '',
                'constraints' => [new Assert\Length(max: 60)],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => AttributeChoiceData::class]);
    }
}
