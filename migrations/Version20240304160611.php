<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240304160611 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE aid_step ADD active TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE aid_type ADD active TINYINT(1) DEFAULT NULL');
        $this->addSql('UPDATE aid_type SET active = 1');
        $this->addSql('UPDATE aid_type SET active = 0 WHERE slug IN (\'technical-engineering\', \'legal-engineering\')');
        $this->addSql('UPDATE aid_step SET active = 1');
        $this->addSql('UPDATE aid_step SET active = 0 WHERE slug IN (\'preop\')');
        $this->addSql("
        INSERT INTO aid_type (aid_type_group_id,name,slug,`position`,time_create,time_update,active) VALUES
            (2,'Ingénierie de planification et stratégie','ingenierie-de-planification-et-strategie',8,'2024-03-04 16:20:57','2024-03-04 16:20:57',1),
            (2,'Ingénierie d’études et diagnostics','ingenierie-detudes-et-diagnostics',9,'2024-03-04 16:21:24','2024-03-04 16:21:24',1),
            (2,'Ingénierie d’animation et mise en réseau','ingenierie-danimation-et-mise-en-reseau',10,'2024-03-04 16:21:45','2024-03-04 16:21:45',1),
            (2,'AMOA/MOD','amoa-mod',11,'2024-03-04 16:21:58','2024-03-04 16:21:58',1),
            (2,'MOE/MOE déléguée','moe-moe-deleguee',12,'2024-03-04 16:22:12','2024-03-04 16:22:12',1),
            (2,'Ingénierie administrative','ingenierie-administrative',13,'2024-03-04 16:28:41','2024-03-04 16:28:41',1),
            (2,'Ingénierie juridique et réglementaire','ingenierie-juridique-et-reglementaire',14,'2024-03-04 16:28:58','2024-03-04 16:28:58',1)
            (2,'Formation/montée en compétence','formation-montee-en-competence',14,'2024-03-04 16:28:58','2024-03-04 16:28:58',1)
        ");
        $this->addSql("
        INSERT INTO aid_step (name,slug,`position`,time_create,time_update,active) VALUES
            ('Émergence / stratégie','emergence-stategie',3,'2024-03-04 15:06:37',NULL,1),
            ('Conception / faisabilité','conception-faisabilite',4,'2024-03-04 15:06:37',NULL,1)
        ");
        $this->addSql("
        UPDATE aid_step SET name='Suivi / évaluation' WHERE slug='postop'
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE aid_step DROP active');
        $this->addSql('ALTER TABLE aid_type DROP active');
    }
}
