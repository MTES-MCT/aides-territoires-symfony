<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240513124337 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE aid_lock (id INT AUTO_INCREMENT NOT NULL, aid_id INT NOT NULL, user_id INT NOT NULL, time_start DATETIME NOT NULL, INDEX IDX_E8A0273ECB0C1416 (aid_id), INDEX IDX_E8A0273EA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE backer_lock (id INT AUTO_INCREMENT NOT NULL, backer_id INT NOT NULL, user_id INT NOT NULL, time_start DATETIME NOT NULL, INDEX IDX_3699235759543840 (backer_id), INDEX IDX_36992357A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE project_lock (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, user_id INT NOT NULL, time_start DATETIME NOT NULL, INDEX IDX_BE1E5316166D1F9C (project_id), INDEX IDX_BE1E5316A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE search_page_lock (id INT AUTO_INCREMENT NOT NULL, search_page_id INT NOT NULL, user_id INT NOT NULL, time_start DATETIME NOT NULL, INDEX IDX_BB50977C81978C7E (search_page_id), INDEX IDX_BB50977CA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE aid_lock ADD CONSTRAINT FK_E8A0273ECB0C1416 FOREIGN KEY (aid_id) REFERENCES aid (id)');
        $this->addSql('ALTER TABLE aid_lock ADD CONSTRAINT FK_E8A0273EA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE backer_lock ADD CONSTRAINT FK_3699235759543840 FOREIGN KEY (backer_id) REFERENCES backer (id)');
        $this->addSql('ALTER TABLE backer_lock ADD CONSTRAINT FK_36992357A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE project_lock ADD CONSTRAINT FK_BE1E5316166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE project_lock ADD CONSTRAINT FK_BE1E5316A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE search_page_lock ADD CONSTRAINT FK_BB50977C81978C7E FOREIGN KEY (search_page_id) REFERENCES search_page (id)');
        $this->addSql('ALTER TABLE search_page_lock ADD CONSTRAINT FK_BB50977CA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE aid_lock DROP FOREIGN KEY FK_E8A0273ECB0C1416');
        $this->addSql('ALTER TABLE aid_lock DROP FOREIGN KEY FK_E8A0273EA76ED395');
        $this->addSql('ALTER TABLE backer_lock DROP FOREIGN KEY FK_3699235759543840');
        $this->addSql('ALTER TABLE backer_lock DROP FOREIGN KEY FK_36992357A76ED395');
        $this->addSql('ALTER TABLE project_lock DROP FOREIGN KEY FK_BE1E5316166D1F9C');
        $this->addSql('ALTER TABLE project_lock DROP FOREIGN KEY FK_BE1E5316A76ED395');
        $this->addSql('ALTER TABLE search_page_lock DROP FOREIGN KEY FK_BB50977C81978C7E');
        $this->addSql('ALTER TABLE search_page_lock DROP FOREIGN KEY FK_BB50977CA76ED395');
        $this->addSql('DROP TABLE aid_lock');
        $this->addSql('DROP TABLE backer_lock');
        $this->addSql('DROP TABLE project_lock');
        $this->addSql('DROP TABLE search_page_lock');
    }
}
