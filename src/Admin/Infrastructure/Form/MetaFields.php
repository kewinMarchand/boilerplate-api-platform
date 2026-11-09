<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Form;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Titre et description du référencement (SPEC-COMMUNE, section 26), facultatifs : vides, le front garde les siens.
 */
final class MetaFields
{
    public const int TITLE_MAX_LENGTH = 60;
    public const int DESCRIPTION_MAX_LENGTH = 160;

    /**
     * @template TData
     *
     * @param FormBuilderInterface<TData> $builder
     */
    public static function add(FormBuilderInterface $builder, string $titleField = 'metaTitle', string $descriptionField = 'metaDescription'): void
    {
        $builder
            ->add($titleField, TextType::class, [
                'label' => 'field.meta_title',
                'required' => false,
                'empty_data' => '',
                'help' => 'help.meta_title',
                'help_translation_parameters' => ['%max%' => self::TITLE_MAX_LENGTH],
                'attr' => ['maxlength' => self::TITLE_MAX_LENGTH],
                'constraints' => [new Assert\Length(max: self::TITLE_MAX_LENGTH)],
            ])
            ->add($descriptionField, TextareaType::class, [
                'label' => 'field.meta_description',
                'required' => false,
                'empty_data' => '',
                'help' => 'help.meta_description',
                'help_translation_parameters' => ['%max%' => self::DESCRIPTION_MAX_LENGTH],
                'attr' => ['maxlength' => self::DESCRIPTION_MAX_LENGTH, 'rows' => 3],
                'constraints' => [new Assert\Length(max: self::DESCRIPTION_MAX_LENGTH)],
            ]);
    }
}
