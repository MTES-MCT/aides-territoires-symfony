<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240830080418 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX active_backer ON backer (active)');
        $this->addSql('CREATE INDEX slug_backer ON backer (slug)');
        $this->addSql('CREATE INDEX is_corporate_backer ON backer (is_corporate)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX active_backer ON backer');
        $this->addSql('DROP INDEX slug_backer ON backer');
        $this->addSql('DROP INDEX is_corporate_backer ON backer');
    }
}
