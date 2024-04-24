<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240424091135 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE aid_lock (id INT AUTO_INCREMENT NOT NULL, aid_id INT NOT NULL, user_id INT NOT NULL, time_start DATETIME NOT NULL, INDEX IDX_E8A0273ECB0C1416 (aid_id), INDEX IDX_E8A0273EA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE aid_lock ADD CONSTRAINT FK_E8A0273ECB0C1416 FOREIGN KEY (aid_id) REFERENCES aid (id)');
        $this->addSql('ALTER TABLE aid_lock ADD CONSTRAINT FK_E8A0273EA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE aid_lock DROP FOREIGN KEY FK_E8A0273ECB0C1416');
        $this->addSql('ALTER TABLE aid_lock DROP FOREIGN KEY FK_E8A0273EA76ED395');
        $this->addSql('DROP TABLE aid_lock');
    }
}
