<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Form;

use App\Content\Application\SlideData;
use App\Content\Domain\Slide;
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
            ->add('image', IntegerType::class, [
                'label' => 'Visuel',
                'help' => \sprintf('Numéro du visuel livré avec les fronts, de 1 à %d.', Slide::IMAGE_COUNT),
                'constraints' => [new Assert\Range(min: 1, max: Slide::IMAGE_COUNT)],
            ])
            ->add('position', IntegerType::class, ['label' => 'Position', 'help' => 'Ordre dans le carrousel, du plus petit au plus grand.']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => SlideData::class]);
    }
}
