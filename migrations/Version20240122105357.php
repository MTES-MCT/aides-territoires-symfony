<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240122105357 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX synonym_aid_fulltext ON aid');
        $this->addSql('CREATE FULLTEXT INDEX synonym_aid_fulltext ON aid (description, eligibility)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX synonym_aid_fulltext ON aid');
        $this->addSql('CREATE FULLTEXT INDEX synonym_aid_fulltext ON aid (name, description, eligibility)');
    }
}
