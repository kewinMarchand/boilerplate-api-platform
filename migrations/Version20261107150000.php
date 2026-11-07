<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261107150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fiche article : texte en Markdown saisi dans l\'admin';
    }

    public function up(Schema $schema): void
    {
        // Les articles existants n'ont pas de texte : leur fiche ne montre que le résumé jusqu'à ce que l'admin en saisisse un
        $this->addSql('ALTER TABLE article ADD body TEXT DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE article ALTER body DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article DROP body');
    }
}
