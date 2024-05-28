<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240514100223 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE backer_ask_associate (id INT AUTO_INCREMENT NOT NULL, backer_id INT NOT NULL, organization_id INT NOT NULL, user_id INT NOT NULL, accepted TINYINT(1) NOT NULL, refused TINYINT(1) NOT NULL, time_create DATETIME NOT NULL, description LONGTEXT DEFAULT NULL, INDEX IDX_5092C07459543840 (backer_id), INDEX IDX_5092C07432C8A3DE (organization_id), INDEX IDX_5092C074A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE backer_ask_associate ADD CONSTRAINT FK_5092C07459543840 FOREIGN KEY (backer_id) REFERENCES backer (id)');
        $this->addSql('ALTER TABLE backer_ask_associate ADD CONSTRAINT FK_5092C07432C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE backer_ask_associate ADD CONSTRAINT FK_5092C074A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE backer_ask_associate DROP FOREIGN KEY FK_5092C07459543840');
        $this->addSql('ALTER TABLE backer_ask_associate DROP FOREIGN KEY FK_5092C07432C8A3DE');
        $this->addSql('ALTER TABLE backer_ask_associate DROP FOREIGN KEY FK_5092C074A76ED395');
        $this->addSql('DROP TABLE backer_ask_associate');
    }
}
