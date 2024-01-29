<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240129144531 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX synonym_aid_fulltext ON aid');
        $this->addSql('CREATE FULLTEXT INDEX synonym_aid_fulltext ON aid (description, eligibility, project_examples)');
        $this->addSql('CREATE INDEX organization_is_imported ON organization (is_imported)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX synonym_aid_fulltext ON aid');
        $this->addSql('CREATE FULLTEXT INDEX synonym_aid_fulltext ON aid (description, eligibility)');
        $this->addSql('DROP INDEX organization_is_imported ON organization');
    }
}
