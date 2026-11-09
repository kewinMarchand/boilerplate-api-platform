<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\EasyAdmin;

use App\Admin\Infrastructure\Form\SiteIdentityType;
use App\Blog\Application\SwitchBlogDefaultLocale;
use App\Catalog\Application\SwitchCatalogDefaultLocale;
use App\Content\Application\SwitchContentDefaultLocale;
use App\Media\Domain\ImageUsage;
use App\Media\Domain\MediaRuleViolation;
use App\Site\Application\GetSiteIdentity;
use App\Site\Application\SiteIdentityData;
use App\Site\Application\UpdateSiteIdentity;
use App\Site\Domain\SiteIdentity;
use App\Site\Domain\SiteRuleViolation;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;

/**
 * Une seule fiche, en modification seule : la page de la section (GET et POST) est son formulaire, rempli par
 * GetSiteIdentity (les valeurs du modèle tant que rien n'est enregistré) et enregistré par le cas d'usage (ADR 0006).
 * Pas de route d'édition par identifiant : la ligne n'existe pas avant le premier enregistrement. Une nouvelle langue
 * par défaut (ADR 0015) bascule aussi les textes du catalogue, du blog et des contenus, dans la même transaction.
 *
 * @extends AbstractCrudController<SiteIdentity>
 */
final class SiteIdentityCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly GetSiteIdentity $getSiteIdentity,
        private readonly UpdateSiteIdentity $updateSiteIdentity,
        private readonly AdminUrlGenerator $urls,
        private readonly AdminMessages $messages,
        private readonly FormLocales $formLocales,
        private readonly SwitchCatalogDefaultLocale $switchCatalog,
        private readonly SwitchBlogDefaultLocale $switchBlog,
        private readonly SwitchContentDefaultLocale $switchContent,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return SiteIdentity::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('entity.site_identity')
            ->setEntityLabelInPlural('entity.site_identity');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW, Action::EDIT, Action::DELETE, Action::BATCH_DELETE, Action::DETAIL);
    }

    #[AdminRoute('/', name: 'index', options: ['methods' => ['GET', 'POST']])]
    public function index(AdminContext $context): Response
    {
        $identity = ($this->getSiteIdentity)();
        $data = SiteIdentityData::fromSiteIdentity($identity);
        $form = $this->createForm(SiteIdentityType::class, $data, $this->formLocales->options());
        $form->handleRequest($context->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->save($data, $identity->getDefaultLocale());
                $this->addFlash('success', $this->messages->trans('flash.site_identity_saved'));

                return $this->redirect($this->urls->setController(self::class)->setAction(Action::INDEX)->generateUrl());
            } catch (SiteRuleViolation|MediaRuleViolation $violation) {
                $form->addError(new FormError($this->messages->violation($violation)));
            }
        }

        return $this->render('admin/form.html.twig', [
            'form' => $form,
            'title' => $this->messages->trans('entity.site_identity'),
            'image_previews' => [
                'imageContent' => ImagePreview::figure($identity->getLogo(), ImageUsage::Logo, 'image.current_logo'),
                'iconContent' => ImagePreview::figure($identity->getIcon(), ImageUsage::Icon, 'image.current_icon'),
                'shareImageContent' => ImagePreview::figure($identity->getShareImage(), ImageUsage::Share, 'image.current_share'),
            ],
            'cancel_url' => $this->urls->setController(self::class)->setAction(Action::INDEX)->generateUrl(),
        ]);
    }

    /**
     * @throws SiteRuleViolation|MediaRuleViolation refus, rien n'est enregistré
     */
    private function save(SiteIdentityData $data, string $previousDefaultLocale): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();
        try {
            ($this->updateSiteIdentity)($data);
            if ($previousDefaultLocale !== $data->defaultLocale) {
                ($this->switchCatalog)($previousDefaultLocale, $data->defaultLocale);
                ($this->switchBlog)($previousDefaultLocale, $data->defaultLocale);
                ($this->switchContent)($previousDefaultLocale, $data->defaultLocale);
            }
            $connection->commit();
        } catch (\Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }
    }
}
