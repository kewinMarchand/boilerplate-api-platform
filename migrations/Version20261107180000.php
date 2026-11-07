<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261107180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Produits à la une, montrés dans le carrousel de la page d\'accueil';
    }

    public function up(Schema $schema): void
    {
        // Aucun produit existant n'est à la une : le carrousel reste vide jusqu'à ce que l'admin en coche
        $this->addSql('ALTER TABLE product ADD featured BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE product ALTER featured DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product DROP featured');
    }
}
