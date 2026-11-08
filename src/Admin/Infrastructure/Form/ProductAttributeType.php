<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Form;

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
                'label' => 'Code',
                'help' => 'Minuscules, chiffres et tirets, commence par une lettre : il apparaît dans les filtres du catalogue. Il ne change plus après la création.',
                'disabled' => $options['code_locked'],
                'constraints' => [new Assert\NotBlank(), new Assert\Regex(self::CODE_PATTERN, 'Minuscules, chiffres et tirets, 40 caractères au plus, commence par une lettre.')],
            ])
            ->add('label', TextType::class, ['label' => 'Libellé', 'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 60)]])
            ->add('type', EnumType::class, [
                'label' => 'Type',
                'class' => AttributeType::class,
                'choice_label' => static fn (AttributeType $type): string => self::typeLabel($type),
            ])
            ->add('unit', TextType::class, [
                'label' => 'Unité',
                'help' => 'Facultative : « mm », « km », « cm ».',
                'required' => false,
                'empty_data' => '',
                'constraints' => [new Assert\Length(max: 10)],
            ])
            ->add('filterable', CheckboxType::class, ['label' => 'Filtre du catalogue', 'help' => 'Liste de valeurs seulement : une facette du catalogue.', 'required' => false])
            ->add('position', IntegerType::class, ['label' => 'Position', 'constraints' => [new Assert\PositiveOrZero()]])
            ->add('choices', CollectionType::class, [
                'label' => 'Valeurs',
                'help' => 'Liste de valeurs seulement, dans l\'ordre d\'affichage. Vider une ligne retire la valeur ; enregistrer ajoute de nouvelles lignes vides.',
                'entry_type' => AttributeChoiceType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ProductAttributeData::class, 'code_locked' => false]);
        $resolver->setAllowedTypes('code_locked', 'bool');
    }

    public static function typeLabel(AttributeType $type): string
    {
        return match ($type) {
            AttributeType::Choice => 'Liste de valeurs',
            AttributeType::Number => 'Nombre',
            AttributeType::Text => 'Texte',
        };
    }
}
