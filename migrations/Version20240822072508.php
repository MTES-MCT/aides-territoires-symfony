<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240822072508 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE log_url_redirect (id INT AUTO_INCREMENT NOT NULL, url_redirect_id INT DEFAULT NULL, ip VARCHAR(50) DEFAULT NULL, referer VARCHAR(700) DEFAULT NULL, request_uri VARCHAR(700) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT NULL, time_create DATETIME NOT NULL, date_create DATE NOT NULL, INDEX IDX_793D6204EC058051 (url_redirect_id), INDEX date_create_lurlred (date_create), INDEX ip_lurlred (ip), INDEX referer_lurlred (referer), INDEX request_uri_lurlred (request_uri), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE url_redirect (id INT AUTO_INCREMENT NOT NULL, old_url VARCHAR(700) NOT NULL, new_url VARCHAR(700) NOT NULL, time_create DATETIME NOT NULL, date_create DATE NOT NULL, INDEX old_url_url_redirect (old_url), INDEX date_create_url_redirect (date_create), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE log_url_redirect ADD CONSTRAINT FK_793D6204EC058051 FOREIGN KEY (url_redirect_id) REFERENCES url_redirect (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE log_url_redirect DROP FOREIGN KEY FK_793D6204EC058051');
        $this->addSql('DROP TABLE log_url_redirect');
        $this->addSql('DROP TABLE url_redirect');
    }
}
