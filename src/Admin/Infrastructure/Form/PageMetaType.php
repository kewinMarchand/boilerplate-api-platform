<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Form;

use App\Admin\Infrastructure\EasyAdmin\AdminMessages;
use App\Content\Application\PageMetaData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<PageMetaData>
 */
final class PageMetaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        MetaFields::add($builder, 'title', 'description');
        TranslationFields::add($builder, $options, static fn (FormBuilderInterface $group) => MetaFields::add($group, 'title', 'description'));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['translation_domain' => AdminMessages::DOMAIN, 'data_class' => PageMetaData::class]);
        TranslationFields::configure($resolver);
    }
}
