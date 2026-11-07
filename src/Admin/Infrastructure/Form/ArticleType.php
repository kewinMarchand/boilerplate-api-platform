<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Form;

use App\Blog\Application\ArticleData;
use App\Media\Domain\ImageUsage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Validation de forme à la frontière ; l'unicité du slug est vérifiée par les cas d'usage.
 *
 * @extends AbstractType<ArticleData>
 */
final class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Titre', 'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255)]])
            ->add('slug', TextType::class, [
                'label' => 'Slug',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255), new Assert\Regex(CategoryType::SLUG_PATTERN, 'Minuscules, chiffres et tirets seulement.')],
            ])
            ->add('excerpt', TextareaType::class, ['label' => 'Résumé', 'empty_data' => '', 'required' => false])
            ->add('body', TextareaType::class, [
                'label' => 'Texte',
                'empty_data' => '',
                'required' => false,
                'attr' => ['rows' => 16],
                'help' => 'Markdown : paragraphes séparés par une ligne vide, ## pour un intertitre, - pour une liste, [texte](adresse) pour un lien. Le HTML saisi est affiché tel quel, sans être interprété.',
            ])
            ->add('publishedAt', DateType::class, ['label' => 'Date de publication', 'input' => 'datetime_immutable', 'widget' => 'single_text']);
        ImageFields::add($builder, ImageUsage::Article, 'Ce que montre l\'image, si elle apporte une information. Une illustration d\'ambiance est décorative.');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ArticleData::class]);
    }
}
