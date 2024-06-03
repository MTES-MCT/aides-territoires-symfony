<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240524100311 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE project_reference_keyword_reference (project_reference_id INT NOT NULL, keyword_reference_id INT NOT NULL, INDEX IDX_1635FA4D3CE23ACA (project_reference_id), INDEX IDX_1635FA4DFD82A2C3 (keyword_reference_id), PRIMARY KEY(project_reference_id, keyword_reference_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_reference_keyword_reference ADD CONSTRAINT FK_1635FA4D3CE23ACA FOREIGN KEY (project_reference_id) REFERENCES project_reference (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_reference_keyword_reference ADD CONSTRAINT FK_1635FA4DFD82A2C3 FOREIGN KEY (keyword_reference_id) REFERENCES keyword_reference (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project_reference_keyword_reference DROP FOREIGN KEY FK_1635FA4D3CE23ACA');
        $this->addSql('ALTER TABLE project_reference_keyword_reference DROP FOREIGN KEY FK_1635FA4DFD82A2C3');
        $this->addSql('DROP TABLE project_reference_keyword_reference');
    }
}
