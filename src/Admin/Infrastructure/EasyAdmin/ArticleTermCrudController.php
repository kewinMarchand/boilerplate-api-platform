<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\EasyAdmin;

use App\Admin\Infrastructure\Form\ArticleTermType;
use App\Blog\Application\ArticleTermData;
use App\Blog\Application\CreateArticleTerm;
use App\Blog\Application\DeleteArticleTerm;
use App\Blog\Application\UpdateArticleTerm;
use App\Blog\Domain\ArticleTerm;
use App\Blog\Domain\ArticleTermKind;
use App\Blog\Domain\BlogRuleViolation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rubriques et formats d'article (ADR 0010) : une même entité, deux sections de l'admin, une par liste. La liste est
 * filtrée sur sa sorte ; création, modification et suppression passent par les cas d'usage du blog.
 *
 * @extends AbstractCrudController<ArticleTerm>
 */
abstract class ArticleTermCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly CreateArticleTerm $createTerm,
        private readonly UpdateArticleTerm $updateTerm,
        private readonly DeleteArticleTerm $deleteTerm,
        private readonly AdminUrlGenerator $urls,
        private readonly AdminMessages $messages,
        private readonly FormLocales $formLocales,
    ) {
    }

    abstract protected static function kind(): ArticleTermKind;

    public static function getEntityFqcn(): string
    {
        return ArticleTerm::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular(ArticleTermKind::Section === static::kind() ? 'entity.section' : 'entity.format')
            ->setEntityLabelInPlural(ArticleTermKind::Section === static::kind() ? 'entity.sections' : 'entity.formats')
            ->setDefaultSort(['position' => 'ASC', 'name' => 'ASC']);
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->andWhere('entity.kind = :kind')
            ->setParameter('kind', static::kind());
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'field.name');
        yield TextField::new('slug', 'field.slug');
        yield IntegerField::new('position', 'field.position');
    }

    public function new(AdminContext $context): Response
    {
        return $this->form($context, new ArticleTermData(), null, $this->messages->trans(ArticleTermKind::Section === static::kind() ? 'title.new_section' : 'title.new_format'));
    }

    public function edit(AdminContext $context): Response
    {
        $term = $context->getEntity()->getInstance();
        \assert($term instanceof ArticleTerm);

        return $this->form($context, ArticleTermData::fromTerm($term), $term->getId(), $this->messages->trans('title.edit', ['%name%' => $term->getName()]));
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        try {
            ($this->deleteTerm)((int) $entityInstance->getId(), static::kind());
        } catch (BlogRuleViolation $violation) {
            $this->addFlash('danger', $this->messages->violation($violation));
        }
    }

    /**
     * @param AdminContext<ArticleTerm> $context
     */
    private function form(AdminContext $context, ArticleTermData $data, ?int $id, string $title): Response
    {
        $form = $this->createForm(ArticleTermType::class, $data, $this->formLocales->options());
        $form->handleRequest($context->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                null === $id ? ($this->createTerm)(static::kind(), $data) : ($this->updateTerm)($id, static::kind(), $data);
                $this->addFlash('success', $this->messages->trans('flash.term_saved', ['%name%' => $data->name]));

                return $this->redirect($this->urls->setController(static::class)->setAction(Action::INDEX)->generateUrl());
            } catch (BlogRuleViolation $violation) {
                $form->addError(new FormError($this->messages->violation($violation)));
            }
        }

        return $this->render('admin/form.html.twig', [
            'form' => $form,
            'title' => $title,
            'cancel_url' => $this->urls->setController(static::class)->setAction(Action::INDEX)->generateUrl(),
        ]);
    }
}
