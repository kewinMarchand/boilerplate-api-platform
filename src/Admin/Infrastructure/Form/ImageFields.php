<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Form;

use App\Media\Domain\ImageUsage;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Champs d'image communs aux formulaires de l'admin (ADR 0002) : le fichier arrive dans la saisie sous forme de
 * contenu (imageContent), les règles (type, poids, dimensions, texte de remplacement) sont vérifiées par StoreImage.
 */
final class ImageFields
{
    /**
     * @template TData
     *
     * @param FormBuilderInterface<TData> $builder
     */
    public static function add(FormBuilderInterface $builder, ImageUsage $usage, string $altHelp): void
    {
        [$ratioWidth, $ratioHeight] = $usage->ratio();
        $builder
            ->add('imageContent', FileType::class, [
                'label' => 'Image',
                'required' => false,
                'data_class' => null,
                'attr' => ['accept' => 'image/jpeg,image/png,image/webp,image/avif'],
                'help' => \sprintf(
                    'JPEG, PNG, WebP ou AVIF, 10 Mo au plus, recadrée au centre au format %d:%d, %d px de large au moins. Sans fichier, l\'image actuelle est gardée.',
                    $ratioWidth,
                    $ratioHeight,
                    $usage->largestWidth(),
                ),
                'invalid_message' => 'Le fichier n\'a pas pu être envoyé (10 Mo au plus).',
            ])
            ->add('imageAlt', TextType::class, [
                'label' => 'Texte de remplacement',
                'required' => false,
                'empty_data' => '',
                'help' => $altHelp,
                'constraints' => [new Assert\Length(max: 255)],
            ])
            ->add('imageDecorative', CheckboxType::class, [
                'label' => 'Image décorative (aucun texte de remplacement)',
                'required' => false,
            ]);

        $builder->get('imageContent')->addModelTransformer(new CallbackTransformer(
            static fn (?string $content): null => null,
            static function (?UploadedFile $file): ?string {
                if (null === $file) {
                    return null;
                }
                if (!$file->isValid()) {
                    throw new TransformationFailedException($file->getErrorMessage());
                }
                $content = file_get_contents($file->getPathname());
                if (false === $content) {
                    throw new TransformationFailedException('Fichier illisible.');
                }

                return $content;
            },
        ));
    }
}
