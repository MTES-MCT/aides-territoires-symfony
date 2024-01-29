<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240129174450 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE aid_keyword_reference (aid_id INT NOT NULL, keyword_reference_id INT NOT NULL, INDEX IDX_F14B9E1ACB0C1416 (aid_id), INDEX IDX_F14B9E1AFD82A2C3 (keyword_reference_id), PRIMARY KEY(aid_id, keyword_reference_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE aid_keyword_reference ADD CONSTRAINT FK_F14B9E1ACB0C1416 FOREIGN KEY (aid_id) REFERENCES aid (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aid_keyword_reference ADD CONSTRAINT FK_F14B9E1AFD82A2C3 FOREIGN KEY (keyword_reference_id) REFERENCES keyword_reference (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE aid_keyword_reference DROP FOREIGN KEY FK_F14B9E1ACB0C1416');
        $this->addSql('ALTER TABLE aid_keyword_reference DROP FOREIGN KEY FK_F14B9E1AFD82A2C3');
        $this->addSql('DROP TABLE aid_keyword_reference');
    }
}
