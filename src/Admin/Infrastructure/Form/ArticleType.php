<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Form;

use App\Blog\Application\ArticleData;
use App\Blog\Domain\Article;
use App\Media\Domain\ImageUsage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Validation de forme à la frontière ; l'unicité du slug, la rubrique, le format et le fichier audio sont vérifiés par
 * les cas d'usage. Le fichier audio passe par son chemin temporaire, jamais en mémoire (ADR 0010).
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
        $builder
            ->add('sectionId', ChoiceType::class, [
                'label' => 'Rubrique',
                'choices' => $options['sections'],
                'required' => false,
                'placeholder' => 'Aucune',
                'help' => 'Liste de la section « Rubriques ».',
            ])
            ->add('formatId', ChoiceType::class, [
                'label' => 'Format',
                'choices' => $options['formats'],
                'required' => false,
                'placeholder' => 'Aucun',
                'help' => 'Liste de la section « Formats ».',
            ])
            ->add('audioPath', FileType::class, [
                'label' => 'Fichier audio',
                'required' => false,
                'data_class' => null,
                'attr' => ['accept' => 'audio/mpeg,audio/mp4,audio/x-m4a,audio/ogg,.mp3,.m4a,.ogg'],
                'help' => 'MP3, M4A ou OGG, 100 Mo au plus. La durée est lue dans le fichier. Sans fichier, l\'audio actuel est gardé.',
                'invalid_message' => 'Le fichier n\'a pas pu être envoyé (100 Mo au plus).',
            ])
            ->add('removeAudio', CheckboxType::class, [
                'label' => 'Retirer le fichier audio',
                'required' => false,
                'help' => 'Un fichier envoyé en même temps est ignoré.',
            ])
            ->add('speaker', TextType::class, [
                'label' => 'Intervenant',
                'required' => false,
                'empty_data' => '',
                'attr' => ['placeholder' => 'Présenté par …', 'maxlength' => Article::SPEAKER_MAX_LENGTH],
                'help' => \sprintf('Affiché avec le lecteur audio, %d caractères au plus.', Article::SPEAKER_MAX_LENGTH),
                'constraints' => [new Assert\Length(max: Article::SPEAKER_MAX_LENGTH)],
            ]);
        $builder->get('audioPath')->addModelTransformer(new CallbackTransformer(
            static fn (?string $path): null => null,
            static function (?UploadedFile $file): ?string {
                if (null === $file) {
                    return null;
                }
                if (!$file->isValid()) {
                    throw new TransformationFailedException($file->getErrorMessage());
                }

                return $file->getPathname();
            },
        ));
        MetaFields::add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ArticleData::class, 'sections' => [], 'formats' => []]);
        $resolver->setAllowedTypes('sections', 'array');
        $resolver->setAllowedTypes('formats', 'array');
    }
}
