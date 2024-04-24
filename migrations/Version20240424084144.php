<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240424084144 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE aid DROP FOREIGN KEY FK_48B40DAA32C8A3DE');
        $this->addSql('ALTER TABLE aid ADD CONSTRAINT FK_48B40DAA32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE aid DROP FOREIGN KEY FK_48B40DAA32C8A3DE');
        $this->addSql('ALTER TABLE aid ADD CONSTRAINT FK_48B40DAA32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
