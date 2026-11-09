<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Form;

use App\Admin\Infrastructure\EasyAdmin\AdminMessages;
use App\Catalog\Application\ProductAttributeData;
use App\Catalog\Domain\AttributeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Validation de forme à la frontière ; les règles du catalogue sont vérifiées par les cas d'usage.
 * Les valeurs sont des lignes fixes (celles de l'attribut plus quelques lignes vides) : pas de JavaScript à maintenir.
 *
 * @extends AbstractType<ProductAttributeData>
 */
final class ProductAttributeType extends AbstractType
{
    public const string CODE_PATTERN = '/^[a-z][a-z0-9-]{0,39}$/';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'field.code',
                'help' => 'help.attribute_code',
                'disabled' => $options['code_locked'],
                'empty_data' => '',
                'constraints' => [new Assert\NotBlank(), new Assert\Regex(self::CODE_PATTERN, 'constraint.attribute_code')],
            ])
            ->add('label', TextType::class, ['label' => 'field.label', 'empty_data' => '', 'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 60)]])
            ->add('type', EnumType::class, [
                'label' => 'field.type',
                'class' => AttributeType::class,
                'choice_label' => static fn (AttributeType $type): string => self::typeLabel($type),
            ])
            ->add('unit', TextType::class, [
                'label' => 'field.unit',
                'help' => 'help.unit',
                'required' => false,
                'empty_data' => '',
                'constraints' => [new Assert\Length(max: 10)],
            ])
            ->add('filterable', CheckboxType::class, ['label' => 'field.filterable', 'help' => 'help.filterable', 'required' => false])
            ->add('position', IntegerType::class, [
                'label' => 'field.position',
                'setter' => static function (ProductAttributeData $data, ?int $position): void { $data->position = $position ?? $data->position; },
                'constraints' => [new Assert\NotBlank(), new Assert\PositiveOrZero()],
            ])
            ->add('choices', CollectionType::class, [
                'label' => 'field.choices',
                'help' => 'help.choices',
                'entry_type' => AttributeChoiceType::class,
                'entry_options' => ['label' => false, 'locales' => $options['locales']],
                'allow_add' => true,
                'allow_delete' => true,
            ]);
        TranslationFields::add($builder, $options, static function (FormBuilderInterface $group): void {
            $group
                ->add('label', TextType::class, ['label' => 'field.label', 'required' => false, 'empty_data' => '', 'constraints' => [new Assert\Length(max: 60)]])
                ->add('unit', TextType::class, ['label' => 'field.unit', 'required' => false, 'empty_data' => '', 'constraints' => [new Assert\Length(max: 10)]]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['translation_domain' => AdminMessages::DOMAIN, 'data_class' => ProductAttributeData::class, 'code_locked' => false]);
        $resolver->setAllowedTypes('code_locked', 'bool');
        TranslationFields::configure($resolver);
    }

    /**
     * Clé de traduction du type.
     */
    public static function typeLabel(AttributeType $type): string
    {
        return 'attribute_type.'.$type->value;
    }
}
