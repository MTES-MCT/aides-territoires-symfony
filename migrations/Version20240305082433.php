<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240305082433 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE aid_type_support (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, time_create DATETIME NOT NULL, time_update DATETIME DEFAULT NULL, active TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE aid ADD aid_type_support_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE aid ADD CONSTRAINT FK_48B40DAAF635BDEE FOREIGN KEY (aid_type_support_id) REFERENCES aid_type_support (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_48B40DAAF635BDEE ON aid (aid_type_support_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE aid DROP FOREIGN KEY FK_48B40DAAF635BDEE');
        $this->addSql('DROP TABLE aid_type_support');
        $this->addSql('DROP INDEX IDX_48B40DAAF635BDEE ON aid');
        $this->addSql('ALTER TABLE aid DROP aid_type_support_id');
    }
}
