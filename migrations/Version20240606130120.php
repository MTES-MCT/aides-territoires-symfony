<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240606130120 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sanctuarized_field (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sanctuarized_field_aid (sanctuarized_field_id INT NOT NULL, aid_id INT NOT NULL, INDEX IDX_A7040197DA257013 (sanctuarized_field_id), INDEX IDX_A7040197CB0C1416 (aid_id), PRIMARY KEY(sanctuarized_field_id, aid_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sanctuarized_field_aid ADD CONSTRAINT FK_A7040197DA257013 FOREIGN KEY (sanctuarized_field_id) REFERENCES sanctuarized_field (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sanctuarized_field_aid ADD CONSTRAINT FK_A7040197CB0C1416 FOREIGN KEY (aid_id) REFERENCES aid (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sanctuarized_field_aid DROP FOREIGN KEY FK_A7040197DA257013');
        $this->addSql('ALTER TABLE sanctuarized_field_aid DROP FOREIGN KEY FK_A7040197CB0C1416');
        $this->addSql('DROP TABLE sanctuarized_field');
        $this->addSql('DROP TABLE sanctuarized_field_aid');
    }
}
