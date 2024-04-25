<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240425144936 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE search_page_lock (id INT AUTO_INCREMENT NOT NULL, search_page_id INT NOT NULL, user_id INT NOT NULL, time_start DATETIME NOT NULL, INDEX IDX_BB50977C81978C7E (search_page_id), INDEX IDX_BB50977CA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE search_page_lock ADD CONSTRAINT FK_BB50977C81978C7E FOREIGN KEY (search_page_id) REFERENCES search_page (id)');
        $this->addSql('ALTER TABLE search_page_lock ADD CONSTRAINT FK_BB50977CA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE search_page ADD organization_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE search_page ADD CONSTRAINT FK_4F10A34932C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_4F10A34932C8A3DE ON search_page (organization_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE search_page_lock DROP FOREIGN KEY FK_BB50977C81978C7E');
        $this->addSql('ALTER TABLE search_page_lock DROP FOREIGN KEY FK_BB50977CA76ED395');
        $this->addSql('DROP TABLE search_page_lock');
        $this->addSql('ALTER TABLE search_page DROP FOREIGN KEY FK_4F10A34932C8A3DE');
        $this->addSql('DROP INDEX IDX_4F10A34932C8A3DE ON search_page');
        $this->addSql('ALTER TABLE search_page DROP organization_id');
    }
}
