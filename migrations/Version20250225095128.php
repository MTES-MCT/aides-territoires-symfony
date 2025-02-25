<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250225095128 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE aid_not_found_error (id INT AUTO_INCREMENT NOT NULL, url VARCHAR(255) DEFAULT NULL, ip VARCHAR(255) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT NULL, referer VARCHAR(255) DEFAULT NULL, reason VARCHAR(255) DEFAULT NULL, date_create DATE NOT NULL, INDEX ip_aid_error (ip), INDEX date_create_aid_error (date_create), INDEX reason_aid_error (reason), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE aid_not_found_error');
    }
}
