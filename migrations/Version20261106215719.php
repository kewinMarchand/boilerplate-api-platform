<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261106215719 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Images envoyées par l\'admin (ADR 0002) : empreinte et texte de remplacement à la place du numéro de visuel';
    }

    public function up(Schema $schema): void
    {
        // Les lignes existantes n'ont pas d'image (image_key nul) : l'admin leur en donne une, ou les fixtures sont rechargées
        $this->addSql('ALTER TABLE article ADD image_key VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD image_alt VARCHAR(255) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE article ALTER image_alt DROP DEFAULT');
        $this->addSql('ALTER TABLE product ADD image_key VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD image_alt VARCHAR(255) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE product ALTER image_alt DROP DEFAULT');
        $this->addSql('ALTER TABLE product DROP image');
        $this->addSql('ALTER TABLE slide ADD image_key VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE slide ADD image_alt VARCHAR(255) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE slide ALTER image_alt DROP DEFAULT');
        $this->addSql('ALTER TABLE slide DROP image');
    }

    public function down(Schema $schema): void
    {
        // Retour au numéro de visuel : le premier pour toutes les lignes
        $this->addSql('ALTER TABLE article DROP image_key');
        $this->addSql('ALTER TABLE article DROP image_alt');
        $this->addSql('ALTER TABLE product ADD image SMALLINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE product ALTER image DROP DEFAULT');
        $this->addSql('ALTER TABLE product DROP image_key');
        $this->addSql('ALTER TABLE product DROP image_alt');
        $this->addSql('ALTER TABLE slide ADD image SMALLINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE slide ALTER image DROP DEFAULT');
        $this->addSql('ALTER TABLE slide DROP image_key');
        $this->addSql('ALTER TABLE slide DROP image_alt');
    }
}
