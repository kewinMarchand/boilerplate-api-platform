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
     * champs ({prefix}Content, {prefix}Alt, {prefix}Decorative) quand un formulaire porte plusieurs images. Libellés et
     * aides : clés du domaine admin.
     *
     * @template TData
     *
     * @param FormBuilderInterface<TData> $builder
     */
    public static function add(FormBuilderInterface $builder, ImageUsage $usage, string $altHelp, string $label = 'field.image', bool $decorative = true, string $prefix = 'image'): void
    {
        self::addFile($builder, $prefix.'Content', $usage, $label);
        $builder->add($prefix.'Alt', TextType::class, [
            'label' => 'field.alt',
            'required' => false,
            'empty_data' => '',
            'help' => $altHelp,
            'constraints' => [new Assert\Length(max: 255)],
        ]);
        if ($decorative) {
            $builder->add($prefix.'Decorative', CheckboxType::class, [
                'label' => 'field.decorative',
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
            'help_translation_parameters' => self::helpParameters($usage),
            'invalid_message' => 'upload.image_failed',
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
        return match (true) {
            null === $usage->ratio() => 'help.image_uncropped',
            ImageUsage::Icon === $usage => 'help.image_icon',
            $usage->minimumWidth() < $usage->largestWidth() => 'help.image_cropped_sharpest',
            default => 'help.image_cropped',
        };
    }

    /**
     * @return array<string, int>
     */
    private static function helpParameters(ImageUsage $usage): array
    {
        $ratio = $usage->ratio() ?? [0, 0];

        return ['%min%' => $usage->minimumWidth(), '%largest%' => $usage->largestWidth(), '%ratioWidth%' => $ratio[0], '%ratioHeight%' => $ratio[1]];
    }
}
