<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261109090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Images des produits : largeur de la plus grande variante d\'une source étroite, galerie (JSON, ordonnée)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product ADD image_width INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD gallery JSON DEFAULT \'[]\' NOT NULL');
        $this->addSql('ALTER TABLE product ALTER gallery DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product DROP image_width');
        $this->addSql('ALTER TABLE product DROP gallery');
    }
}
