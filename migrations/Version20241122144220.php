<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241122144220 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE aid ADD last_editor_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE aid ADD CONSTRAINT FK_48B40DAA7E5A734A FOREIGN KEY (last_editor_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_48B40DAA7E5A734A ON aid (last_editor_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE aid DROP FOREIGN KEY FK_48B40DAA7E5A734A');
        $this->addSql('DROP INDEX IDX_48B40DAA7E5A734A ON aid');
        $this->addSql('ALTER TABLE aid DROP last_editor_id');
    }
}
