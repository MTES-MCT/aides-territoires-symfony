<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240229144736 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE search_page ADD search_page_redirect_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE search_page ADD CONSTRAINT FK_4F10A34994029A5 FOREIGN KEY (search_page_redirect_id) REFERENCES search_page (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_4F10A34994029A5 ON search_page (search_page_redirect_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE search_page DROP FOREIGN KEY FK_4F10A34994029A5');
        $this->addSql('DROP INDEX IDX_4F10A34994029A5 ON search_page');
        $this->addSql('ALTER TABLE search_page DROP search_page_redirect_id');
    }
}
