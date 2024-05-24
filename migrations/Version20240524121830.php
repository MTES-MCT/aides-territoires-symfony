<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240524121830 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE log_backer_edit (id INT AUTO_INCREMENT NOT NULL, backer_id INT NOT NULL, user_id INT DEFAULT NULL, organization_id INT DEFAULT NULL, timecreate DATETIME NOT NULL, INDEX IDX_6C9A1AEB59543840 (backer_id), INDEX IDX_6C9A1AEBA76ED395 (user_id), INDEX IDX_6C9A1AEB32C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE log_backer_edit ADD CONSTRAINT FK_6C9A1AEB59543840 FOREIGN KEY (backer_id) REFERENCES backer (id)');
        $this->addSql('ALTER TABLE log_backer_edit ADD CONSTRAINT FK_6C9A1AEBA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_backer_edit ADD CONSTRAINT FK_6C9A1AEB32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE log_backer_edit DROP FOREIGN KEY FK_6C9A1AEB59543840');
        $this->addSql('ALTER TABLE log_backer_edit DROP FOREIGN KEY FK_6C9A1AEBA76ED395');
        $this->addSql('ALTER TABLE log_backer_edit DROP FOREIGN KEY FK_6C9A1AEB32C8A3DE');
        $this->addSql('DROP TABLE log_backer_edit');
    }
}
