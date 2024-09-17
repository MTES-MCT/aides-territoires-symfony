<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240917115030 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE alert ADD date_create DATE DEFAULT NULL');
        $this->addSql('CREATE INDEX date_create_alert ON alert (date_create)');
        $this->addSql('CREATE INDEX slug_orgt ON organization_type (slug)');
        $this->addSql('CREATE INDEX is_obsolete_peri ON perimeter (is_obsolete)');
        $this->addSql('UPDATE alert SET date_create = DATE(time_create)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX date_create_alert ON alert');
        $this->addSql('ALTER TABLE alert DROP date_create');
        $this->addSql('DROP INDEX slug_orgt ON organization_type');
        $this->addSql('DROP INDEX is_obsolete_peri ON perimeter');
    }
}
