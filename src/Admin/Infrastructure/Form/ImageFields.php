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
     * Champs d'une image : fichier, texte de remplacement et, sauf exception, case « décorative ». Le préfixe nomme les
     * champs ({prefix}Content, {prefix}Alt, {prefix}Decorative) quand un formulaire porte plusieurs images.
     *
     * @template TData
     *
     * @param FormBuilderInterface<TData> $builder
     */
    public static function add(FormBuilderInterface $builder, ImageUsage $usage, string $altHelp, string $label = 'Image', bool $decorative = true, string $prefix = 'image'): void
    {
        self::addFile($builder, $prefix.'Content', $usage, $label);
        $builder->add($prefix.'Alt', TextType::class, [
            'label' => 'Texte de remplacement',
            'required' => false,
            'empty_data' => '',
            'help' => $altHelp,
            'constraints' => [new Assert\Length(max: 255)],
        ]);
        if ($decorative) {
            $builder->add($prefix.'Decorative', CheckboxType::class, [
                'label' => 'Image décorative (aucun texte de remplacement)',
                'required' => false,
            ]);
        }
    }

    /**
     * Champ d'envoi seul, dont le fichier arrive dans la saisie sous forme de contenu.
     *
     * @template TData
     *
     * @param FormBuilderInterface<TData> $builder
     */
    public static function addFile(FormBuilderInterface $builder, string $name, ImageUsage $usage, string $label): void
    {
        $builder->add($name, FileType::class, [
            'label' => $label,
            'required' => false,
            'data_class' => null,
            'attr' => ['accept' => 'image/jpeg,image/png,image/webp,image/avif'],
            'help' => self::help($usage),
            'invalid_message' => 'Le fichier n\'a pas pu être envoyé (10 Mo au plus).',
        ]);
        $builder->get($name)->addModelTransformer(new CallbackTransformer(
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

    private static function help(ImageUsage $usage): string
    {
        $ratio = $usage->ratio();
        if (null === $ratio) {
            return \sprintf('PNG, WebP ou AVIF (transparence gardée) ou JPEG, 10 Mo au plus, sans recadrage, %d px de large au moins. Pas de SVG. Sans fichier, l\'image actuelle est gardée.', $usage->minimumWidth());
        }
        if (ImageUsage::Icon === $usage) {
            return \sprintf('PNG, WebP ou AVIF (transparence gardée), carrée, %d px de large au moins, recadrée au centre. Pas de SVG ni d\'ICO. Les favicons sont générés en PNG de 32, 180, 192 et 512 px.', $usage->minimumWidth());
        }

        return \sprintf(
            'JPEG, PNG, WebP ou AVIF, 10 Mo au plus, recadrée au centre au format %d:%d, %d px de large au moins%s. Sans fichier, l\'image actuelle est gardée.',
            $ratio[0],
            $ratio[1],
            $usage->minimumWidth(),
            $usage->minimumWidth() < $usage->largestWidth() ? \sprintf(', %d px pour la meilleure netteté (jamais agrandie)', $usage->largestWidth()) : '',
        );
    }
}
