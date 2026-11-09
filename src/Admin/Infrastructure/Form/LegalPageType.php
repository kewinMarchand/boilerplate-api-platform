<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Form;

use App\Admin\Infrastructure\EasyAdmin\AdminMessages;
use App\Content\Application\LegalPageData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<LegalPageData>
 */
final class LegalPageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'field.title', 'empty_data' => '', 'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255)]])
            ->add('body', TextareaType::class, [
                'label' => 'field.markdown_body',
                'help' => 'help.legal_body',
                'attr' => ['rows' => 24],
                'empty_data' => '',
                'constraints' => [new Assert\NotBlank()],
            ]);
        TranslationFields::add($builder, $options, static function (FormBuilderInterface $group): void {
            $group
                ->add('title', TextType::class, ['label' => 'field.title', 'required' => false, 'empty_data' => '', 'constraints' => [new Assert\Length(max: 255)]])
                ->add('body', TextareaType::class, ['label' => 'field.markdown_body', 'required' => false, 'empty_data' => '', 'attr' => ['rows' => 24]]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['translation_domain' => AdminMessages::DOMAIN, 'data_class' => LegalPageData::class]);
        TranslationFields::configure($resolver);
    }
}
