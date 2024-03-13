<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240312153715 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE backer_user (id INT AUTO_INCREMENT NOT NULL, backer_id INT NOT NULL, user_id INT NOT NULL, administrator TINYINT(1) NOT NULL, editor TINYINT(1) NOT NULL, time_create DATETIME NOT NULL, time_update DATETIME DEFAULT NULL, INDEX IDX_3C856E1059543840 (backer_id), INDEX IDX_3C856E10A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE backer_user ADD CONSTRAINT FK_3C856E1059543840 FOREIGN KEY (backer_id) REFERENCES backer (id)');
        $this->addSql('ALTER TABLE backer_user ADD CONSTRAINT FK_3C856E10A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE backer_user DROP FOREIGN KEY FK_3C856E1059543840');
        $this->addSql('ALTER TABLE backer_user DROP FOREIGN KEY FK_3C856E10A76ED395');
        $this->addSql('DROP TABLE backer_user');
    }
}
