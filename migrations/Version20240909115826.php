<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240909115826 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE log_aid_search_temp (id INT AUTO_INCREMENT NOT NULL, perimeter_id INT DEFAULT NULL, organization_id INT DEFAULT NULL, user_id INT DEFAULT NULL, querystring LONGTEXT DEFAULT NULL, results_count INT NOT NULL, source VARCHAR(255) DEFAULT NULL, time_create DATETIME NOT NULL, date_create DATE NOT NULL, search VARCHAR(255) DEFAULT NULL, INDEX IDX_3718118F77570A4C (perimeter_id), INDEX IDX_3718118F32C8A3DE (organization_id), INDEX IDX_3718118FA76ED395 (user_id), INDEX date_create_last (date_create), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE log_aid_search_temp_organization_type (log_aid_search_temp_id INT NOT NULL, organization_type_id INT NOT NULL, INDEX IDX_7303C099CC9D22FC (log_aid_search_temp_id), INDEX IDX_7303C09989E04D0 (organization_type_id), PRIMARY KEY(log_aid_search_temp_id, organization_type_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE log_aid_search_temp_backer (log_aid_search_temp_id INT NOT NULL, backer_id INT NOT NULL, INDEX IDX_E9B7122CCC9D22FC (log_aid_search_temp_id), INDEX IDX_E9B7122C59543840 (backer_id), PRIMARY KEY(log_aid_search_temp_id, backer_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE log_aid_search_temp_category (log_aid_search_temp_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_6C3F4736CC9D22FC (log_aid_search_temp_id), INDEX IDX_6C3F473612469DE2 (category_id), PRIMARY KEY(log_aid_search_temp_id, category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE log_aid_search_temp_program (log_aid_search_temp_id INT NOT NULL, program_id INT NOT NULL, INDEX IDX_51937295CC9D22FC (log_aid_search_temp_id), INDEX IDX_519372953EB8070A (program_id), PRIMARY KEY(log_aid_search_temp_id, program_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE log_aid_search_temp_category_theme (log_aid_search_temp_id INT NOT NULL, category_theme_id INT NOT NULL, INDEX IDX_CC38B1CECC9D22FC (log_aid_search_temp_id), INDEX IDX_CC38B1CEEE78F0FC (category_theme_id), PRIMARY KEY(log_aid_search_temp_id, category_theme_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE log_aid_view_temp (id INT AUTO_INCREMENT NOT NULL, aid_id INT DEFAULT NULL, organization_id INT DEFAULT NULL, user_id INT DEFAULT NULL, querystring LONGTEXT DEFAULT NULL, source VARCHAR(255) DEFAULT NULL, time_create DATETIME NOT NULL, date_create DATE NOT NULL, INDEX IDX_ED7521C8CB0C1416 (aid_id), INDEX IDX_ED7521C832C8A3DE (organization_id), INDEX IDX_ED7521C8A76ED395 (user_id), INDEX date_create_lavt (date_create), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE log_aid_search_temp ADD CONSTRAINT FK_3718118F77570A4C FOREIGN KEY (perimeter_id) REFERENCES perimeter (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_aid_search_temp ADD CONSTRAINT FK_3718118F32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_aid_search_temp ADD CONSTRAINT FK_3718118FA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_aid_search_temp_organization_type ADD CONSTRAINT FK_7303C099CC9D22FC FOREIGN KEY (log_aid_search_temp_id) REFERENCES log_aid_search_temp (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE log_aid_search_temp_organization_type ADD CONSTRAINT FK_7303C09989E04D0 FOREIGN KEY (organization_type_id) REFERENCES organization_type (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE log_aid_search_temp_backer ADD CONSTRAINT FK_E9B7122CCC9D22FC FOREIGN KEY (log_aid_search_temp_id) REFERENCES log_aid_search_temp (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE log_aid_search_temp_backer ADD CONSTRAINT FK_E9B7122C59543840 FOREIGN KEY (backer_id) REFERENCES backer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE log_aid_search_temp_category ADD CONSTRAINT FK_6C3F4736CC9D22FC FOREIGN KEY (log_aid_search_temp_id) REFERENCES log_aid_search_temp (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE log_aid_search_temp_category ADD CONSTRAINT FK_6C3F473612469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE log_aid_search_temp_program ADD CONSTRAINT FK_51937295CC9D22FC FOREIGN KEY (log_aid_search_temp_id) REFERENCES log_aid_search_temp (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE log_aid_search_temp_program ADD CONSTRAINT FK_519372953EB8070A FOREIGN KEY (program_id) REFERENCES program (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE log_aid_search_temp_category_theme ADD CONSTRAINT FK_CC38B1CECC9D22FC FOREIGN KEY (log_aid_search_temp_id) REFERENCES log_aid_search_temp (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE log_aid_search_temp_category_theme ADD CONSTRAINT FK_CC38B1CEEE78F0FC FOREIGN KEY (category_theme_id) REFERENCES category_theme (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE log_aid_view_temp ADD CONSTRAINT FK_ED7521C8CB0C1416 FOREIGN KEY (aid_id) REFERENCES aid (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_aid_view_temp ADD CONSTRAINT FK_ED7521C832C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_aid_view_temp ADD CONSTRAINT FK_ED7521C8A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE log_aid_search_temp DROP FOREIGN KEY FK_3718118F77570A4C');
        $this->addSql('ALTER TABLE log_aid_search_temp DROP FOREIGN KEY FK_3718118F32C8A3DE');
        $this->addSql('ALTER TABLE log_aid_search_temp DROP FOREIGN KEY FK_3718118FA76ED395');
        $this->addSql('ALTER TABLE log_aid_search_temp_organization_type DROP FOREIGN KEY FK_7303C099CC9D22FC');
        $this->addSql('ALTER TABLE log_aid_search_temp_organization_type DROP FOREIGN KEY FK_7303C09989E04D0');
        $this->addSql('ALTER TABLE log_aid_search_temp_backer DROP FOREIGN KEY FK_E9B7122CCC9D22FC');
        $this->addSql('ALTER TABLE log_aid_search_temp_backer DROP FOREIGN KEY FK_E9B7122C59543840');
        $this->addSql('ALTER TABLE log_aid_search_temp_category DROP FOREIGN KEY FK_6C3F4736CC9D22FC');
        $this->addSql('ALTER TABLE log_aid_search_temp_category DROP FOREIGN KEY FK_6C3F473612469DE2');
        $this->addSql('ALTER TABLE log_aid_search_temp_program DROP FOREIGN KEY FK_51937295CC9D22FC');
        $this->addSql('ALTER TABLE log_aid_search_temp_program DROP FOREIGN KEY FK_519372953EB8070A');
        $this->addSql('ALTER TABLE log_aid_search_temp_category_theme DROP FOREIGN KEY FK_CC38B1CECC9D22FC');
        $this->addSql('ALTER TABLE log_aid_search_temp_category_theme DROP FOREIGN KEY FK_CC38B1CEEE78F0FC');
        $this->addSql('ALTER TABLE log_aid_view_temp DROP FOREIGN KEY FK_ED7521C8CB0C1416');
        $this->addSql('ALTER TABLE log_aid_view_temp DROP FOREIGN KEY FK_ED7521C832C8A3DE');
        $this->addSql('ALTER TABLE log_aid_view_temp DROP FOREIGN KEY FK_ED7521C8A76ED395');
        $this->addSql('DROP TABLE log_aid_search_temp');
        $this->addSql('DROP TABLE log_aid_search_temp_organization_type');
        $this->addSql('DROP TABLE log_aid_search_temp_backer');
        $this->addSql('DROP TABLE log_aid_search_temp_category');
        $this->addSql('DROP TABLE log_aid_search_temp_program');
        $this->addSql('DROP TABLE log_aid_search_temp_category_theme');
        $this->addSql('DROP TABLE log_aid_view_temp');
    }
}
