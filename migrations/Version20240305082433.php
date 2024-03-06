<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240305082433 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE aid_type_support (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, time_create DATETIME NOT NULL, time_update DATETIME DEFAULT NULL, active TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE aid ADD aid_type_support_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE aid ADD CONSTRAINT FK_48B40DAAF635BDEE FOREIGN KEY (aid_type_support_id) REFERENCES aid_type_support (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_48B40DAAF635BDEE ON aid (aid_type_support_id)');
        $this->addSql("
        INSERT INTO aid_type (aid_type_group_id,name,slug,`position`,time_create,time_update,active) VALUES
            (2,'Ingénierie de planification et stratégie','ingenierie-de-planification-et-strategie',8,'2024-03-04 16:20:57','2024-03-04 16:20:57',1),
            (2,'Ingénierie d’études et diagnostics','ingenierie-detudes-et-diagnostics',9,'2024-03-04 16:21:24','2024-03-04 16:21:24',1),
            (2,'Ingénierie d’animation et mise en réseau','ingenierie-danimation-et-mise-en-reseau',10,'2024-03-04 16:21:45','2024-03-04 16:21:45',1),
            (2,'AMOA/MOD','amoa-mod',11,'2024-03-04 16:21:58','2024-03-04 16:21:58',1),
            (2,'MOE/MOE déléguée','moe-moe-deleguee',12,'2024-03-04 16:22:12','2024-03-04 16:22:12',1),
            (2,'Ingénierie administrative','ingenierie-administrative',13,'2024-03-04 16:28:41','2024-03-04 16:28:41',1),
            (2,'Ingénierie juridique et réglementaire','ingenierie-juridique-et-reglementaire',14,'2024-03-04 16:28:58','2024-03-04 16:28:58',1)
        ");
        $this->addSql("
        INSERT INTO aid_step (name,slug,`position`,time_create,time_update,active) VALUES
            ('Émergence / stratégie','emergence-stategie',3,'2024-03-04 15:06:37',NULL,1),
            ('Conception / faisabilité','conception-faisabilite',4,'2024-03-04 15:06:37',NULL,1)
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE aid DROP FOREIGN KEY FK_48B40DAAF635BDEE');
        $this->addSql('DROP TABLE aid_type_support');
        $this->addSql('DROP INDEX IDX_48B40DAAF635BDEE ON aid');
        $this->addSql('ALTER TABLE aid DROP aid_type_support_id');
    }
}
