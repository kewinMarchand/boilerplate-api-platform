<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Form;

use App\Admin\Infrastructure\EasyAdmin\AdminMessages;
use App\Blog\Application\ArticleTermData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Rubrique ou format d'article (ADR 0010). Validation de forme à la frontière ; l'unicité du slug est vérifiée par
 * les cas d'usage.
 *
 * @extends AbstractType<ArticleTermData>
 */
final class ArticleTermType extends AbstractType
{
    public const string SLUG_PATTERN = '/^[a-z0-9][a-z0-9-]{0,59}$/';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'field.name', 'empty_data' => '', 'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 60)]])
            ->add('slug', TextType::class, [
                'label' => 'field.slug',
                'help' => 'help.term_slug',
                'empty_data' => '',
                'constraints' => [new Assert\NotBlank(), new Assert\Regex(self::SLUG_PATTERN, 'constraint.term_slug')],
            ])
            ->add('position', IntegerType::class, ['label' => 'field.position', 'constraints' => [new Assert\PositiveOrZero()]]);
        TranslationFields::add($builder, $options, static function (FormBuilderInterface $group): void {
            $group
                ->add('name', TextType::class, ['label' => 'field.name', 'required' => false, 'empty_data' => '', 'constraints' => [new Assert\Length(max: 60)]])
                ->add('slug', TextType::class, ['label' => 'field.slug', 'required' => false, 'empty_data' => '', 'help' => 'help.translated_slug', 'constraints' => [new Assert\Regex(self::SLUG_PATTERN, 'constraint.term_slug')]]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['translation_domain' => AdminMessages::DOMAIN, 'data_class' => ArticleTermData::class]);
        TranslationFields::configure($resolver);
    }
}
