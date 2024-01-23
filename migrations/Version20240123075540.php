<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240123075540 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cron_export_spreadsheet (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, sql_request LONGTEXT NOT NULL, sql_params JSON DEFAULT NULL, filename VARCHAR(255) NOT NULL, format VARCHAR(255) NOT NULL, time_email DATETIME DEFAULT NULL, processing TINYINT(1) NOT NULL, INDEX IDX_9D82063BA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cron_export_spreadsheet ADD CONSTRAINT FK_9D82063BA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cron_export_spreadsheet DROP FOREIGN KEY FK_9D82063BA76ED395');
        $this->addSql('DROP TABLE cron_export_spreadsheet');
    }
}
