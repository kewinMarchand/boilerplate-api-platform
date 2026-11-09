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
        private readonly AdminMessages $messages,
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
            ->setEntityLabelInSingular('entity.article')
            ->setEntityLabelInPlural('entity.articles')
            ->setDefaultSort(['publishedAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield ImagePreview::column(ImageUsage::Article, static fn (object $article): ?Image => $article instanceof Article ? $article->getImage() : null);
        yield TextField::new('title', 'field.title');
        yield TextField::new('slug', 'field.slug');
        yield DateField::new('publishedAt', 'field.publication');
        yield TextField::new('sectionName', 'field.section')
            ->setVirtual(true)
            ->setTemplatePath(self::TEXT_TEMPLATE)
            ->formatValue(static fn (mixed $value, Article $article): string => $article->getSection()?->getName() ?? '—');
        yield TextField::new('excerpt', 'field.excerpt')->hideOnIndex();
        yield TextareaField::new('body', 'field.markdown_text')->onlyOnDetail();
    }

    public function new(AdminContext $context): Response
    {
        return $this->form($context, new ArticleData(), null, $this->messages->trans('title.new_article'));
    }

    public function edit(AdminContext $context): Response
    {
        $article = $context->getEntity()->getInstance();
        \assert($article instanceof Article);

        return $this->form($context, ArticleData::fromArticle($article), $article->getId(), $this->messages->trans('title.edit', ['%name%' => $article->getTitle()]), $article->getImage());
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        try {
            ($this->deleteArticle)((int) $entityInstance->getId());
        } catch (BlogRuleViolation $violation) {
            $this->addFlash('danger', $this->messages->violation($violation));
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
                $this->addFlash('success', $this->messages->trans('flash.article_saved', ['%name%' => $data->title]));

                return $this->redirect($this->urls->setController(self::class)->setAction(Action::INDEX)->generateUrl());
            } catch (BlogRuleViolation|MediaRuleViolation $violation) {
                $form->addError(new FormError($this->messages->violation($violation)));
            }
        }

        return $this->render('admin/form.html.twig', [
            'form' => $form,
            'title' => $title,
            'image_previews' => ['imageContent' => ImagePreview::figure($image, ImageUsage::Article)],
            'audio_preview' => null === $data->currentAudio ? null : [
                'src' => '/media/'.$data->currentAudio->fileName(),
                'caption' => $this->messages->trans('audio.current', ['%format%' => strtoupper($data->currentAudio->format->value), '%seconds%' => $data->currentAudio->durationSeconds]),
            ],
            'cancel_url' => $this->urls->setController(self::class)->setAction(Action::INDEX)->generateUrl(),
        ]);
    }
}
