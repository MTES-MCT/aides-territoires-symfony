<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240422120725 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE organization_access (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, organization_id INT NOT NULL, administrator TINYINT(1) NOT NULL, edit_aid TINYINT(1) NOT NULL, edit_portal TINYINT(1) NOT NULL, edit_backer TINYINT(1) NOT NULL, edit_project TINYINT(1) NOT NULL, time_create DATETIME NOT NULL, time_update DATETIME DEFAULT NULL, INDEX IDX_3E7FBA1A76ED395 (user_id), INDEX IDX_3E7FBA132C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE organization_access ADD CONSTRAINT FK_3E7FBA1A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE organization_access ADD CONSTRAINT FK_3E7FBA132C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('
        INSERT INTO organization_access
        (user_id, organization_id, administrator, edit_aid, edit_portal, edit_backer, edit_project, time_create)
        select ou.user_id , ou.organization_id, 1, 1, 1, 1, 1, \'2024-04-22 14:15:00\'
        from organization_user ou 
        ');
        $this->addSql('TRUNCATE organization_user');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE organization_access DROP FOREIGN KEY FK_3E7FBA1A76ED395');
        $this->addSql('ALTER TABLE organization_access DROP FOREIGN KEY FK_3E7FBA132C8A3DE');
        $this->addSql('DROP TABLE organization_access');
    }
}
