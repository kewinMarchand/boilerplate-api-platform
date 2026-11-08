<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Form;

use App\Media\Domain\ImageUsage;
use App\Site\Application\SiteIdentityData;
use App\Site\Domain\DefaultColorScheme;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
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
            ->add('name', TextType::class, ['label' => 'Nom du site', 'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 60)]])
            ->add('tagline', TextType::class, [
                'label' => 'Accroche',
                'required' => false,
                'empty_data' => '',
                'help' => 'Texte du hero de l\'accueil et description par défaut des pages.',
                'constraints' => [new Assert\Length(max: 160)],
            ]);
        ImageFields::add($builder, ImageUsage::Logo, 'Vide : le nom du site.', 'Logo', decorative: false);
        $builder
            ->add('removeLogo', CheckboxType::class, [
                'label' => 'Retirer le logo',
                'required' => false,
                'help' => 'Le header affiche alors le pictogramme et le nom du site. Un fichier envoyé en même temps est ignoré.',
            ])
            ->add('primaryColor', TextType::class, [
                'label' => 'Couleur principale',
                'required' => false,
                'empty_data' => '',
                'attr' => ['placeholder' => '#rrggbb'],
                'help' => 'Couleur des liens et des boutons : elle doit contraster à 4,5:1 au moins avec le fond blanc. Les deux couleurs vides : celles de la bibliothèque. Le thème sombre et les déclinaisons sont calculés.',
            ])
            ->add('secondaryColor', TextType::class, [
                'label' => 'Couleur secondaire',
                'required' => false,
                'empty_data' => '',
                'attr' => ['placeholder' => '#rrggbb'],
                'help' => 'Elle doit porter un texte lisible (blanc ou quasi-noir à 4,5:1 au moins).',
            ])
            ->add('defaultColorScheme', EnumType::class, [
                'label' => 'Thème par défaut',
                'class' => DefaultColorScheme::class,
                'help' => 'Thème servi tant que le visiteur n\'a rien choisi.',
                'choice_label' => static fn (DefaultColorScheme $scheme): string => match ($scheme) {
                    DefaultColorScheme::Auto => 'Celui du système du visiteur',
                    DefaultColorScheme::Light => 'Clair',
                    DefaultColorScheme::Dark => 'Sombre',
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => SiteIdentityData::class]);
    }
}
