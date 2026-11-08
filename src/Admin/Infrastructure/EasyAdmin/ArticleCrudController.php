<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\EasyAdmin;

use App\Admin\Infrastructure\Form\ArticleType;
use App\Blog\Application\ArticleData;
use App\Blog\Application\CreateArticle;
use App\Blog\Application\DeleteArticle;
use App\Blog\Application\UpdateArticle;
use App\Blog\Domain\Article;
use App\Blog\Domain\ArticleTerm;
use App\Blog\Domain\ArticleTermKind;
use App\Blog\Domain\ArticleTermRepository;
use App\Blog\Domain\BlogRuleViolation;
use App\Media\Domain\Image;
use App\Media\Domain\ImageUsage;
use App\Media\Domain\MediaRuleViolation;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;

/**
 * Listes et fiches par EasyAdmin ; création, modification et suppression par les cas d'usage du blog (ADR 0001).
 *
 * @extends AbstractCrudController<Article>
 */
final class ArticleCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly CreateArticle $createArticle,
        private readonly UpdateArticle $updateArticle,
        private readonly DeleteArticle $deleteArticle,
        private readonly ArticleTermRepository $terms,
        private readonly AdminUrlGenerator $urls,
    ) {
    }

    /** Modèle texte imposé aux champs virtuels : sans lui, EasyAdmin les affiche « Inaccessible », avant même formatValue. */
    private const TEXT_TEMPLATE = '@EasyAdmin/crud/field/text.html.twig';

    public static function getEntityFqcn(): string
    {
        return Article::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Article')
            ->setEntityLabelInPlural('Articles')
            ->setDefaultSort(['publishedAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield ImagePreview::column(ImageUsage::Article, static fn (object $article): ?Image => $article instanceof Article ? $article->getImage() : null);
        yield TextField::new('title', 'Titre');
        yield TextField::new('slug', 'Slug');
        yield DateField::new('publishedAt', 'Publication');
        yield TextField::new('sectionName', 'Rubrique')
            ->setVirtual(true)
            ->setTemplatePath(self::TEXT_TEMPLATE)
            ->formatValue(static fn (mixed $value, Article $article): string => $article->getSection()?->getName() ?? '—');
        yield TextField::new('excerpt', 'Résumé')->hideOnIndex();
        yield TextareaField::new('body', 'Texte (Markdown)')->onlyOnDetail();
    }

    public function new(AdminContext $context): Response
    {
        return $this->form($context, new ArticleData(), null, 'Nouvel article');
    }

    public function edit(AdminContext $context): Response
    {
        $article = $context->getEntity()->getInstance();
        \assert($article instanceof Article);

        return $this->form($context, ArticleData::fromArticle($article), $article->getId(), \sprintf('Modifier « %s »', $article->getTitle()), $article->getImage());
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        try {
            ($this->deleteArticle)((int) $entityInstance->getId());
        } catch (BlogRuleViolation $violation) {
            $this->addFlash('danger', $violation->getMessage());
        }
    }

    /**
     * @param AdminContext<Article> $context
     */
    private function form(AdminContext $context, ArticleData $data, ?int $id, string $title, ?Image $image = null): Response
    {
        $choices = fn (ArticleTermKind $kind): array => array_column(array_map(static fn (ArticleTerm $term): array => [$term->getName(), $term->getId()], $this->terms->findByKind($kind)), 1, 0);
        $form = $this->createForm(ArticleType::class, $data, ['sections' => $choices(ArticleTermKind::Section), 'formats' => $choices(ArticleTermKind::Format)]);
        $form->handleRequest($context->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                null === $id ? ($this->createArticle)($data) : ($this->updateArticle)($id, $data);
                $this->addFlash('success', \sprintf('Article « %s » enregistré.', $data->title));

                return $this->redirect($this->urls->setController(self::class)->setAction(Action::INDEX)->generateUrl());
            } catch (BlogRuleViolation|MediaRuleViolation $violation) {
                $form->addError(new FormError($violation->getMessage()));
            }
        }

        return $this->render('admin/form.html.twig', [
            'form' => $form,
            'title' => $title,
            'image_previews' => ['imageContent' => ImagePreview::figure($image, ImageUsage::Article)],
            'audio_preview' => null === $data->currentAudio ? null : [
                'src' => '/media/'.$data->currentAudio->fileName(),
                'caption' => \sprintf('Fichier audio actuel, %s, %d s', strtoupper($data->currentAudio->format->value), $data->currentAudio->durationSeconds),
            ],
            'cancel_url' => $this->urls->setController(self::class)->setAction(Action::INDEX)->generateUrl(),
        ]);
    }
}
