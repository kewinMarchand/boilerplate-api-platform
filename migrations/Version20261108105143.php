<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261108105143 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Identité du site, une seule ligne : sans elle, l\'API sert les valeurs du modèle';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE site_identity (name VARCHAR(60) NOT NULL, tagline VARCHAR(160) NOT NULL, logo_key VARCHAR(32) DEFAULT NULL, logo_alt VARCHAR(255) NOT NULL, logo_height INT DEFAULT NULL, primary_color VARCHAR(7) DEFAULT NULL, secondary_color VARCHAR(7) DEFAULT NULL, default_color_scheme VARCHAR(5) NOT NULL, id INT NOT NULL, PRIMARY KEY (id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE site_identity');
    }
}
