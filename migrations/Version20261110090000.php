<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261110090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Vente en ligne et demande de renseignement : case de vente et adresse qui reçoit les messages du site, produit du message de contact';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_identity ADD sales_enabled BOOLEAN DEFAULT true NOT NULL');
        $this->addSql('ALTER TABLE site_identity ALTER sales_enabled DROP DEFAULT');
        $this->addSql('ALTER TABLE site_identity ADD contact_recipient VARCHAR(255) DEFAULT \'contact@example.com\' NOT NULL');
        $this->addSql('ALTER TABLE site_identity ALTER contact_recipient DROP DEFAULT');
        $this->addSql('ALTER TABLE contact_message ADD product_slug VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_identity DROP sales_enabled');
        $this->addSql('ALTER TABLE site_identity DROP contact_recipient');
        $this->addSql('ALTER TABLE contact_message DROP product_slug');
    }
}
