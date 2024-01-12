<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240112094344 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE log_aid_application_url_click DROP FOREIGN KEY FK_881A137CB0C1416');
        $this->addSql('ALTER TABLE log_aid_application_url_click ADD CONSTRAINT FK_881A137CB0C1416 FOREIGN KEY (aid_id) REFERENCES aid (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_aid_contact_click DROP FOREIGN KEY FK_34526D66CB0C1416');
        $this->addSql('ALTER TABLE log_aid_contact_click ADD CONSTRAINT FK_34526D66CB0C1416 FOREIGN KEY (aid_id) REFERENCES aid (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_aid_createds_folder DROP FOREIGN KEY FK_874772CECB0C1416');
        $this->addSql('ALTER TABLE log_aid_createds_folder ADD CONSTRAINT FK_874772CECB0C1416 FOREIGN KEY (aid_id) REFERENCES aid (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_aid_eligibility_test DROP FOREIGN KEY FK_7D453FD1CB0C1416');
        $this->addSql('ALTER TABLE log_aid_eligibility_test ADD CONSTRAINT FK_7D453FD1CB0C1416 FOREIGN KEY (aid_id) REFERENCES aid (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_aid_origin_url_click DROP FOREIGN KEY FK_D4CA439DCB0C1416');
        $this->addSql('ALTER TABLE log_aid_origin_url_click ADD CONSTRAINT FK_D4CA439DCB0C1416 FOREIGN KEY (aid_id) REFERENCES aid (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_aid_view DROP FOREIGN KEY FK_FF81DC61CB0C1416');
        $this->addSql('ALTER TABLE log_aid_view ADD CONSTRAINT FK_FF81DC61CB0C1416 FOREIGN KEY (aid_id) REFERENCES aid (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE log_aid_application_url_click DROP FOREIGN KEY FK_881A137CB0C1416');
        $this->addSql('ALTER TABLE log_aid_application_url_click ADD CONSTRAINT FK_881A137CB0C1416 FOREIGN KEY (aid_id) REFERENCES aid (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE log_aid_contact_click DROP FOREIGN KEY FK_34526D66CB0C1416');
        $this->addSql('ALTER TABLE log_aid_contact_click ADD CONSTRAINT FK_34526D66CB0C1416 FOREIGN KEY (aid_id) REFERENCES aid (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE log_aid_createds_folder DROP FOREIGN KEY FK_874772CECB0C1416');
        $this->addSql('ALTER TABLE log_aid_createds_folder ADD CONSTRAINT FK_874772CECB0C1416 FOREIGN KEY (aid_id) REFERENCES aid (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE log_aid_eligibility_test DROP FOREIGN KEY FK_7D453FD1CB0C1416');
        $this->addSql('ALTER TABLE log_aid_eligibility_test ADD CONSTRAINT FK_7D453FD1CB0C1416 FOREIGN KEY (aid_id) REFERENCES aid (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE log_aid_origin_url_click DROP FOREIGN KEY FK_D4CA439DCB0C1416');
        $this->addSql('ALTER TABLE log_aid_origin_url_click ADD CONSTRAINT FK_D4CA439DCB0C1416 FOREIGN KEY (aid_id) REFERENCES aid (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE log_aid_view DROP FOREIGN KEY FK_FF81DC61CB0C1416');
        $this->addSql('ALTER TABLE log_aid_view ADD CONSTRAINT FK_FF81DC61CB0C1416 FOREIGN KEY (aid_id) REFERENCES aid (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
