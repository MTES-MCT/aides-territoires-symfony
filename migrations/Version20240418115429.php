<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240418115429 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE aid_type SET position = 0 WHERE slug = "grant"');
        $this->addSql('UPDATE aid_type SET position = 1 WHERE slug = "loan"');
        $this->addSql('UPDATE aid_type SET position = 2 WHERE slug = "recoverable-advance"');
        $this->addSql('UPDATE aid_type SET position = 3 WHERE slug = "cee"');
        $this->addSql('UPDATE aid_type SET position = 4 WHERE slug = "other"');
        $this->addSql('UPDATE aid_type SET position = 5 WHERE slug = "technical-engineering"');
        $this->addSql('UPDATE aid_type SET position = 6 WHERE slug = "financial-engineering"');
        $this->addSql('UPDATE aid_type SET position = 7 WHERE slug = "ingenierie-de-planification-et-strategie"');
        $this->addSql('UPDATE aid_type SET position = 8 WHERE slug = "ingenierie-detudes-et-diagnostics"');
        $this->addSql('UPDATE aid_type SET position = 9 WHERE slug = "ingenierie-danimation-et-mise-en-reseau"');
        $this->addSql('UPDATE aid_type SET position = 10 WHERE slug = "amoa-mod"');
        $this->addSql('UPDATE aid_type SET position = 11 WHERE slug = "moe-moe-deleguee"');
        $this->addSql('UPDATE aid_type SET position = 12 WHERE slug = "formation-montee-en-competence"');
        $this->addSql('UPDATE aid_type SET position = 13 WHERE slug = "legal-engineering"');
        $this->addSql('UPDATE aid_type SET position = 14 WHERE slug = "ingenierie-juridique-et-reglementaire"');
        $this->addSql('UPDATE aid_type SET position = 15 WHERE slug = "ingenierie-administrative"');
    }

    public function down(Schema $schema): void
    {
    }
}
