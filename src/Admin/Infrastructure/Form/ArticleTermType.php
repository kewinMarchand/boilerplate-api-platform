<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Form;

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
            ->add('name', TextType::class, ['label' => 'Nom', 'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 60)]])
            ->add('slug', TextType::class, [
                'label' => 'Slug',
                'help' => 'Minuscules, chiffres et tirets, 60 caractères au plus : il apparaît dans les adresses du blog.',
                'constraints' => [new Assert\NotBlank(), new Assert\Regex(self::SLUG_PATTERN, 'Minuscules, chiffres et tirets, 60 caractères au plus, sans tiret au début.')],
            ])
            ->add('position', IntegerType::class, ['label' => 'Position', 'constraints' => [new Assert\PositiveOrZero()]]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ArticleTermData::class]);
    }
}
