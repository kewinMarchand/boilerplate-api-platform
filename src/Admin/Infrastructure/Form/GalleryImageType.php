<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Form;

use App\Admin\Infrastructure\EasyAdmin\ImagePreview;
use App\Catalog\Application\GalleryImageData;
use App\Media\Domain\ImageUsage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Une ligne de la galerie d'un produit (ADR 0009) : image enregistrée (aperçu, texte, position, retrait, remplacement)
 * ou ligne d'ajout, ignorée sans fichier. Rendue par le bloc gallery_image_row de admin/form_theme.html.twig.
 *
 * @extends AbstractType<GalleryImageData>
 */
final class GalleryImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        ImageFields::addFile($builder, 'content', ImageUsage::Product, 'Fichier');
        $builder
            ->add('alt', TextType::class, [
                'label' => 'Texte de remplacement',
                'required' => false,
                'empty_data' => '',
                'help' => 'Vide : le nom du produit.',
                'constraints' => [new Assert\Length(max: 255)],
            ])
            ->add('position', IntegerType::class, ['label' => 'Position', 'empty_data' => '0', 'constraints' => [new Assert\PositiveOrZero()]]);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event): void {
            $data = $event->getData();
            if ($data instanceof GalleryImageData && null !== $data->current) {
                $event->getForm()->add('remove', CheckboxType::class, ['label' => 'Retirer cette image', 'required' => false]);
            }
        });
    }

    /**
     * @param FormInterface<GalleryImageData> $form
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var GalleryImageData|null $data null pour le prototype de la collection */
        $data = $form->getData();
        $view->vars['image_preview'] = ImagePreview::figure($data?->current, ImageUsage::Product);
    }

    public function getBlockPrefix(): string
    {
        return 'gallery_image';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => GalleryImageData::class, 'label' => false]);
    }
}
