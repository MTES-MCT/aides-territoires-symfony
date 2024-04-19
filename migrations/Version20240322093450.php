<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240322093450 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE organization DROP FOREIGN KEY FK_C1EE637C59543840');
        $this->addSql('ALTER TABLE organization ADD CONSTRAINT FK_C1EE637C59543840 FOREIGN KEY (backer_id) REFERENCES backer (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE organization DROP FOREIGN KEY FK_C1EE637C59543840');
        $this->addSql('ALTER TABLE organization ADD CONSTRAINT FK_C1EE637C59543840 FOREIGN KEY (backer_id) REFERENCES backer (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
