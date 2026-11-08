<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\EasyAdmin;

use App\Contact\Domain\ContactMessage;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Messages reçus par le contact et demandes de renseignement (section 27), en lecture seule :
 * un mail en échec ne laisse le message qu'en base.
 *
 * @extends AbstractCrudController<ContactMessage>
 */
final class ContactMessageCrudController extends AbstractCrudController
{
    /** Modèle texte imposé aux champs virtuels : sans lui, EasyAdmin les affiche « Inaccessible », avant même formatValue. */
    private const TEXT_TEMPLATE = '@EasyAdmin/crud/field/text.html.twig';

    public static function getEntityFqcn(): string
    {
        return ContactMessage::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Message')
            ->setEntityLabelInPlural('Messages')
            ->setPageTitle(Crud::PAGE_DETAIL, static fn (ContactMessage $message): string => \sprintf('Message de %s', $message->getName()))
            ->setDefaultSort(['receivedAt' => 'DESC', 'id' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW, Action::EDIT, Action::DELETE, Action::BATCH_DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield DateTimeField::new('receivedAt', 'Reçu le')->setFormat('dd/MM/yyyy HH:mm')->setTimezone('Europe/Paris');
        yield TextField::new('name', 'Nom');
        yield TextField::new('email', 'Adresse e-mail');
        // Champ virtuel : « Aucun » plutôt que le badge « Aucun(e) » d'EasyAdmin, comme l'admin Hono
        yield TextField::new('productLabel', 'Produit')
            ->setVirtual(true)
            ->setTemplatePath(self::TEXT_TEMPLATE)
            ->formatValue(static fn (mixed $value, ContactMessage $message): string => $message->getProductSlug() ?? 'Aucun');
        yield TextareaField::new('message', 'Message');
    }
}
