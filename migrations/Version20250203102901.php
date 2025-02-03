<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250203102901 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE favorite_aid (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, aid_id INT NOT NULL, date_create DATE NOT NULL, INDEX IDX_40CC1EA8A76ED395 (user_id), INDEX IDX_40CC1EA8CB0C1416 (aid_id), INDEX date_create_favorite_aid (date_create), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE favorite_aid ADD CONSTRAINT FK_40CC1EA8A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE favorite_aid ADD CONSTRAINT FK_40CC1EA8CB0C1416 FOREIGN KEY (aid_id) REFERENCES aid (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE favorite_aid DROP FOREIGN KEY FK_40CC1EA8A76ED395');
        $this->addSql('ALTER TABLE favorite_aid DROP FOREIGN KEY FK_40CC1EA8CB0C1416');
        $this->addSql('DROP TABLE favorite_aid');
    }
}
