<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240322094454 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE backer ADD backer_type LONGTEXT DEFAULT NULL, ADD projects_examples LONGTEXT DEFAULT NULL, ADD internal_operation LONGTEXT DEFAULT NULL, ADD contact LONGTEXT DEFAULT NULL, ADD useful_links LONGTEXT DEFAULT NULL');
        $this->addSql('UPDATE organization_type SET name="Etablissement public dont services de l\'Etat" where slug = "public-org"');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE backer DROP backer_type, DROP projects_examples, DROP internal_operation, DROP contact, DROP useful_links');
    }
}
