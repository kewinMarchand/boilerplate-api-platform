<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Contenu multilingue (ADR 0015) : les colonnes existantes deviennent la langue par défaut, le français, sans être
 * touchées ; chaque entité traduisible gagne une colonne `translations` vide, et l'identité ses langues, ["fr"].
 */
final class Version20261111090000 extends AbstractMigration
{
    private const array TABLES = ['product', 'category', 'product_attribute', 'article', 'article_term', 'legal_page', 'page_meta', 'site_identity'];

    public function getDescription(): string
    {
        return 'Contenu multilingue : traductions des entités traduisibles et langues du site (le texte actuel reste celui de la langue par défaut, fr)';
    }

    public function up(Schema $schema): void
    {
        foreach (self::TABLES as $table) {
            $this->addSql(\sprintf('ALTER TABLE %s ADD translations JSON DEFAULT \'{}\' NOT NULL', $table));
            $this->addSql(\sprintf('ALTER TABLE %s ALTER translations DROP DEFAULT', $table));
        }
        $this->addSql('ALTER TABLE site_identity ADD locales JSON DEFAULT \'["fr"]\' NOT NULL');
        $this->addSql('ALTER TABLE site_identity ALTER locales DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        foreach (self::TABLES as $table) {
            $this->addSql(\sprintf('ALTER TABLE %s DROP translations', $table));
        }
        $this->addSql('ALTER TABLE site_identity DROP locales');
    }
}
