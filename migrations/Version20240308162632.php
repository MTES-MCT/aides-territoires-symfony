<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240308162632 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE faq (id INT AUTO_INCREMENT NOT NULL, page_tab_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, time_create DATETIME NOT NULL, time_update DATETIME DEFAULT NULL, INDEX IDX_E8FF75CCE2DA653D (page_tab_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE faq ADD CONSTRAINT FK_E8FF75CCE2DA653D FOREIGN KEY (page_tab_id) REFERENCES page_tab (id)');
        $this->addSql('ALTER TABLE faq_category ADD faq_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE faq_category ADD CONSTRAINT FK_FAEEE0D681BEC8C2 FOREIGN KEY (faq_id) REFERENCES faq (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_FAEEE0D681BEC8C2 ON faq_category (faq_id)');
        $this->addSql('ALTER TABLE page_tab ADD active TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE faq_category DROP FOREIGN KEY FK_FAEEE0D681BEC8C2');
        $this->addSql('ALTER TABLE faq DROP FOREIGN KEY FK_E8FF75CCE2DA653D');
        $this->addSql('DROP TABLE faq');
        $this->addSql('DROP INDEX IDX_FAEEE0D681BEC8C2 ON faq_category');
        $this->addSql('ALTER TABLE faq_category DROP faq_id');
        $this->addSql('ALTER TABLE page_tab DROP active');
    }
}
