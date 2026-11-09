<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Form;

use App\Admin\Infrastructure\EasyAdmin\AdminMessages;
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
            ->add('title', TextType::class, ['label' => 'field.title', 'empty_data' => '', 'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255)]])
            ->add('slug', TextType::class, [
                'label' => 'field.slug',
                'empty_data' => '',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255), new Assert\Regex(CategoryType::SLUG_PATTERN, 'constraint.slug')],
            ])
            ->add('excerpt', TextareaType::class, ['label' => 'field.excerpt', 'empty_data' => '', 'required' => false])
            ->add('body', TextareaType::class, [
                'label' => 'field.body',
                'empty_data' => '',
                'required' => false,
                'attr' => ['rows' => 16],
                'help' => 'help.article_body',
            ])
            ->add('publishedAt', DateType::class, [
                'label' => 'field.published_at',
                'input' => 'datetime_immutable',
                'widget' => 'single_text',
                'setter' => static function (ArticleData $data, ?\DateTimeImmutable $publishedAt): void { $data->publishedAt = $publishedAt ?? $data->publishedAt; },
                'constraints' => [new Assert\NotBlank()],
            ]);
        ImageFields::add($builder, ImageUsage::Article, 'help.article_alt');
        $builder
            ->add('sectionId', ChoiceType::class, [
                'label' => 'field.section',
                'choices' => $options['sections'],
                'choice_translation_domain' => false,
                'required' => false,
                'placeholder' => 'article.no_section',
                'help' => 'help.article_section',
            ])
            ->add('formatId', ChoiceType::class, [
                'label' => 'field.format',
                'choices' => $options['formats'],
                'choice_translation_domain' => false,
                'required' => false,
                'placeholder' => 'article.no_format',
                'help' => 'help.article_format',
            ])
            ->add('audioPath', FileType::class, [
                'label' => 'field.audio',
                'required' => false,
                'data_class' => null,
                'attr' => ['accept' => 'audio/mpeg,audio/mp4,audio/x-m4a,audio/ogg,.mp3,.m4a,.ogg'],
                'help' => 'help.audio',
                'invalid_message' => 'upload.audio_failed',
            ])
            ->add('removeAudio', CheckboxType::class, [
                'label' => 'field.remove_audio',
                'required' => false,
                'help' => 'help.remove_ignores_upload',
            ])
            ->add('speaker', TextType::class, [
                'label' => 'field.speaker',
                'required' => false,
                'empty_data' => '',
                'attr' => ['placeholder' => 'article.speaker_placeholder', 'maxlength' => Article::SPEAKER_MAX_LENGTH],
                'help' => 'help.speaker',
                'help_translation_parameters' => ['%max%' => Article::SPEAKER_MAX_LENGTH],
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
        TranslationFields::add($builder, $options, static function (FormBuilderInterface $group): void {
            $group
                ->add('title', TextType::class, ['label' => 'field.title', 'required' => false, 'empty_data' => '', 'constraints' => [new Assert\Length(max: 255)]])
                ->add('slug', TextType::class, ['label' => 'field.slug', 'required' => false, 'empty_data' => '', 'help' => 'help.translated_slug', 'constraints' => [new Assert\Length(max: 255), new Assert\Regex(CategoryType::SLUG_PATTERN, 'constraint.slug')]])
                ->add('excerpt', TextareaType::class, ['label' => 'field.excerpt', 'required' => false, 'empty_data' => ''])
                ->add('body', TextareaType::class, ['label' => 'field.body', 'required' => false, 'empty_data' => '', 'attr' => ['rows' => 16]])
                ->add('imageAlt', TextType::class, ['label' => 'field.alt', 'required' => false, 'empty_data' => '', 'constraints' => [new Assert\Length(max: 255)]])
                ->add('speaker', TextType::class, ['label' => 'field.speaker', 'required' => false, 'empty_data' => '', 'attr' => ['maxlength' => Article::SPEAKER_MAX_LENGTH], 'constraints' => [new Assert\Length(max: Article::SPEAKER_MAX_LENGTH)]]);
            MetaFields::add($group);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['translation_domain' => AdminMessages::DOMAIN, 'data_class' => ArticleData::class, 'sections' => [], 'formats' => []]);
        $resolver->setAllowedTypes('sections', 'array');
        $resolver->setAllowedTypes('formats', 'array');
        TranslationFields::configure($resolver);
    }
}
