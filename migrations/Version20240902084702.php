<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240902084702 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE project_reference_missing (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE project_reference_missing_aid (project_reference_missing_id INT NOT NULL, aid_id INT NOT NULL, INDEX IDX_B5E534798CF9CE4A (project_reference_missing_id), INDEX IDX_B5E53479CB0C1416 (aid_id), PRIMARY KEY(project_reference_missing_id, aid_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_reference_missing_aid ADD CONSTRAINT FK_B5E534798CF9CE4A FOREIGN KEY (project_reference_missing_id) REFERENCES project_reference_missing (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_reference_missing_aid ADD CONSTRAINT FK_B5E53479CB0C1416 FOREIGN KEY (aid_id) REFERENCES aid (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project_reference_missing_aid DROP FOREIGN KEY FK_B5E534798CF9CE4A');
        $this->addSql('ALTER TABLE project_reference_missing_aid DROP FOREIGN KEY FK_B5E53479CB0C1416');
        $this->addSql('DROP TABLE project_reference_missing');
        $this->addSql('DROP TABLE project_reference_missing_aid');
    }
}
