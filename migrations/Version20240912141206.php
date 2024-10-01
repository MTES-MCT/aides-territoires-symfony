<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240912141206 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE search_page_user (search_page_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_B14CDA3B81978C7E (search_page_id), INDEX IDX_B14CDA3BA76ED395 (user_id), PRIMARY KEY(search_page_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE search_page_user ADD CONSTRAINT FK_B14CDA3B81978C7E FOREIGN KEY (search_page_id) REFERENCES search_page (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE search_page_user ADD CONSTRAINT FK_B14CDA3BA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE search_page_user DROP FOREIGN KEY FK_B14CDA3B81978C7E');
        $this->addSql('ALTER TABLE search_page_user DROP FOREIGN KEY FK_B14CDA3BA76ED395');
        $this->addSql('DROP TABLE search_page_user');
    }
}
