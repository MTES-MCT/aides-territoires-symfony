<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240216142714 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE aid ADD date_check_broken_link DATE DEFAULT NULL');
        $this->addSql('CREATE INDEX date_check_broken_link_aid ON aid (date_check_broken_link)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX date_check_broken_link_aid ON aid');
        $this->addSql('ALTER TABLE aid DROP date_check_broken_link');
    }
}
