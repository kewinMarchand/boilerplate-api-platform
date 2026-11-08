<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Form;

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
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => PageMetaData::class]);
    }
}
