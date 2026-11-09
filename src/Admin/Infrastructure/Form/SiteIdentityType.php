<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Form;

use App\Admin\Infrastructure\EasyAdmin\AdminMessages;
use App\Media\Domain\ImageUsage;
use App\Site\Application\SiteIdentityData;
use App\Site\Domain\DefaultColorScheme;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Validation de forme à la frontière ; le format, le couple et le contraste des couleurs sont vérifiés par le domaine.
 *
 * @extends AbstractType<SiteIdentityData>
 */
final class SiteIdentityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'field.site_name', 'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 60)]])
            ->add('tagline', TextType::class, [
                'label' => 'field.tagline',
                'required' => false,
                'empty_data' => '',
                'help' => 'help.tagline',
                'constraints' => [new Assert\Length(max: 160)],
            ]);
        ImageFields::add($builder, ImageUsage::Logo, 'help.site_alt', 'field.logo', decorative: false);
        $builder
            ->add('removeLogo', CheckboxType::class, [
                'label' => 'field.remove_logo',
                'required' => false,
                'help' => 'help.remove_logo',
            ]);
        ImageFields::addFile($builder, 'iconContent', ImageUsage::Icon, 'field.icon');
        $builder->add('removeIcon', CheckboxType::class, [
            'label' => 'field.remove_icon',
            'required' => false,
            'help' => 'help.remove_icon',
        ]);
        ImageFields::add($builder, ImageUsage::Share, 'help.site_alt', 'field.share_image', decorative: false, prefix: 'shareImage');
        $builder
            ->add('removeShareImage', CheckboxType::class, [
                'label' => 'field.remove_share_image',
                'required' => false,
                'help' => 'help.remove_share_image',
            ])
            ->add('useLibraryColors', CheckboxType::class, [
                'label' => 'field.library_colors',
                'required' => false,
                'help' => 'help.library_colors',
            ])
            ->add('primaryColor', ColorType::class, [
                'label' => 'field.primary_color',
                'required' => false,
                'empty_data' => '',
                'html5' => true,
                'help' => 'help.primary_color',
            ])
            ->add('secondaryColor', ColorType::class, [
                'label' => 'field.secondary_color',
                'required' => false,
                'empty_data' => '',
                'html5' => true,
                'help' => 'help.secondary_color',
            ])
            ->add('defaultColorScheme', EnumType::class, [
                'label' => 'field.color_scheme',
                'class' => DefaultColorScheme::class,
                'help' => 'help.color_scheme',
                'choice_label' => static fn (DefaultColorScheme $scheme): string => 'color_scheme.'.$scheme->value,
            ])
            ->add('salesEnabled', CheckboxType::class, [
                'label' => 'field.sales',
                'required' => false,
                'help' => 'help.sales',
            ])
            ->add('contactRecipient', EmailType::class, [
                'label' => 'field.contact_recipient',
                'empty_data' => '',
                'help' => 'help.contact_recipient',
                'constraints' => [new Assert\NotBlank(), new Assert\Email(mode: Assert\Email::VALIDATION_MODE_STRICT), new Assert\Length(max: 255)],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['translation_domain' => AdminMessages::DOMAIN, 'data_class' => SiteIdentityData::class]);
    }
}
