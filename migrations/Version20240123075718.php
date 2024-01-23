<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240123075718 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cron_export_spreadsheet DROP FOREIGN KEY FK_9D82063BA76ED395');
        $this->addSql('ALTER TABLE cron_export_spreadsheet ADD CONSTRAINT FK_9D82063BA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cron_export_spreadsheet DROP FOREIGN KEY FK_9D82063BA76ED395');
        $this->addSql('ALTER TABLE cron_export_spreadsheet ADD CONSTRAINT FK_9D82063BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
