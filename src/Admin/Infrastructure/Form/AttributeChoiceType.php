<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Form;

use App\Catalog\Application\AttributeChoiceData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
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
        // Langues traduites (ADR 0015) : un texte par langue, dans la ligne ; vide, celui de la langue par défaut
        /** @var list<string> $locales */
        $locales = $options['locales'];
        if ([] !== $locales) {
            $texts = $builder->create('labels', FormType::class, ['label' => false, 'required' => false]);
            foreach ($locales as $locale) {
                $texts->add($locale, TextType::class, [
                    'label' => 'field.label_in',
                    'label_translation_parameters' => ['%language%' => ContentLanguages::name($locale)],
                    'required' => false,
                    'empty_data' => '',
                    'constraints' => [new Assert\Length(max: 60)],
                ]);
            }
            $builder->add($texts);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => AttributeChoiceData::class]);
        $resolver->setDefault('locales', []);
        $resolver->setAllowedTypes('locales', 'string[]');
    }
}
