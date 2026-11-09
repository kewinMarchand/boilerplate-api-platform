<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Form;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Champs traduisibles d'une fiche (SPEC-COMMUNE, section 29, ADR 0015) : un groupe `translations.<langue>` par langue
 * activée autre que la langue par défaut, rien sur un site monolingue. Les champs y sont facultatifs : vide, la langue
 * par défaut sert. admin/form.html.twig les range en onglets, la langue par défaut en premier.
 */
final class TranslationFields
{
    /**
     * Options communes des formulaires traduisibles : langue par défaut et autres langues activées.
     */
    public static function configure(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['default_locale' => 'fr', 'locales' => []]);
        $resolver->setAllowedTypes('default_locale', 'string');
        $resolver->setAllowedTypes('locales', 'string[]');
    }

    /**
     * @template TData
     *
     * @param FormBuilderInterface<TData>                 $builder
     * @param array<string, mixed>                        $options options du formulaire (default_locale, locales)
     * @param callable(FormBuilderInterface<mixed>): void $fields  ajoute les champs d'une langue
     */
    public static function add(FormBuilderInterface $builder, array $options, callable $fields): void
    {
        /** @var list<string> $locales */
        $locales = $options['locales'];
        if ([] === $locales) {
            return;
        }
        $translations = $builder->create('translations', FormType::class, [
            'label' => false,
            'required' => false,
            'attr' => ['data-default-locale' => $options['default_locale']],
        ]);
        foreach ($locales as $locale) {
            $group = $translations->create($locale, FormType::class, ['label' => false, 'required' => false]);
            $fields($group);
            $translations->add($group);
        }
        $builder->add($translations);
    }
}
