<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261108090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Description des produits, affichée sur leur fiche';
    }

    public function up(Schema $schema): void
    {
        // Les produits existants n'ont pas de description : la fiche n'en montre pas jusqu'à ce que l'admin en écrive une
        $this->addSql("ALTER TABLE product ADD description TEXT DEFAULT '' NOT NULL");
        $this->addSql('ALTER TABLE product ALTER description DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product DROP description');
    }
}
