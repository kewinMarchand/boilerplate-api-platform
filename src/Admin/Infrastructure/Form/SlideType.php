<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Form;

use App\Content\Application\SlideData;
use App\Media\Domain\ImageUsage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Validation de forme à la frontière ; les règles des contenus sont vérifiées par les cas d'usage.
 *
 * @extends AbstractType<SlideData>
 */
final class SlideType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Titre', 'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255)]])
            ->add('slug', TextType::class, [
                'label' => 'Slug',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255), new Assert\Regex(CategoryType::SLUG_PATTERN, 'Minuscules, chiffres et tirets seulement.')],
            ])
            ->add('text', TextareaType::class, ['label' => 'Texte', 'empty_data' => '', 'required' => false])
            ->add('position', IntegerType::class, ['label' => 'Position', 'help' => 'Ordre dans le carrousel, du plus petit au plus grand.']);
        ImageFields::add($builder, ImageUsage::Slide, 'Ce que montre la photo, pour qui ne la voit pas. Le titre de la diapositive est déjà lu à côté.');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => SlideData::class]);
    }
}
