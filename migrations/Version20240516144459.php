<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240516144459 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE keyword_reference_suggested (id INT AUTO_INCREMENT NOT NULL, keyword_reference_id INT NOT NULL, aid_id INT NOT NULL, occurence INT NOT NULL, INDEX IDX_708D7EB6FD82A2C3 (keyword_reference_id), INDEX IDX_708D7EB6CB0C1416 (aid_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE keyword_reference_suggested ADD CONSTRAINT FK_708D7EB6FD82A2C3 FOREIGN KEY (keyword_reference_id) REFERENCES keyword_reference (id)');
        $this->addSql('ALTER TABLE keyword_reference_suggested ADD CONSTRAINT FK_708D7EB6CB0C1416 FOREIGN KEY (aid_id) REFERENCES aid (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE keyword_reference_suggested DROP FOREIGN KEY FK_708D7EB6FD82A2C3');
        $this->addSql('ALTER TABLE keyword_reference_suggested DROP FOREIGN KEY FK_708D7EB6CB0C1416');
        $this->addSql('DROP TABLE keyword_reference_suggested');
    }
}
