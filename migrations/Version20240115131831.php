<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240115131831 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE log_aid_createds_folder DROP FOREIGN KEY FK_874772CE32C8A3DE');
        $this->addSql('ALTER TABLE log_aid_createds_folder ADD CONSTRAINT FK_874772CE32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_aid_eligibility_test DROP FOREIGN KEY FK_7D453FD1D81AE339');
        $this->addSql('ALTER TABLE log_aid_eligibility_test ADD CONSTRAINT FK_7D453FD1D81AE339 FOREIGN KEY (eligibility_test_id) REFERENCES eligibility_test (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_aid_search DROP FOREIGN KEY FK_A377D16732C8A3DE');
        $this->addSql('ALTER TABLE log_aid_search DROP FOREIGN KEY FK_A377D16777570A4C');
        $this->addSql('ALTER TABLE log_aid_search ADD CONSTRAINT FK_A377D16732C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_aid_search ADD CONSTRAINT FK_A377D16777570A4C FOREIGN KEY (perimeter_id) REFERENCES perimeter (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_aid_view DROP FOREIGN KEY FK_FF81DC6132C8A3DE');
        $this->addSql('ALTER TABLE log_aid_view ADD CONSTRAINT FK_FF81DC6132C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_backer_view DROP FOREIGN KEY FK_13526F7F32C8A3DE');
        $this->addSql('ALTER TABLE log_backer_view DROP FOREIGN KEY FK_13526F7F59543840');
        $this->addSql('ALTER TABLE log_backer_view ADD CONSTRAINT FK_13526F7F32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_backer_view ADD CONSTRAINT FK_13526F7F59543840 FOREIGN KEY (backer_id) REFERENCES backer (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_blog_post_view DROP FOREIGN KEY FK_748A159C32C8A3DE');
        $this->addSql('ALTER TABLE log_blog_post_view DROP FOREIGN KEY FK_748A159CA77FBEAF');
        $this->addSql('ALTER TABLE log_blog_post_view ADD CONSTRAINT FK_748A159C32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_blog_post_view ADD CONSTRAINT FK_748A159CA77FBEAF FOREIGN KEY (blog_post_id) REFERENCES blog_post (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_blog_promotion_post_click DROP FOREIGN KEY FK_15E9548D5AE9B170');
        $this->addSql('ALTER TABLE log_blog_promotion_post_click ADD CONSTRAINT FK_15E9548D5AE9B170 FOREIGN KEY (blog_promotion_post_id) REFERENCES blog_promotion_post (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_blog_promotion_post_display DROP FOREIGN KEY FK_E1429695AE9B170');
        $this->addSql('ALTER TABLE log_blog_promotion_post_display ADD CONSTRAINT FK_E1429695AE9B170 FOREIGN KEY (blog_promotion_post_id) REFERENCES blog_promotion_post (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_program_view DROP FOREIGN KEY FK_2B4A6C2F32C8A3DE');
        $this->addSql('ALTER TABLE log_program_view DROP FOREIGN KEY FK_2B4A6C2F3EB8070A');
        $this->addSql('ALTER TABLE log_program_view ADD CONSTRAINT FK_2B4A6C2F32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_program_view ADD CONSTRAINT FK_2B4A6C2F3EB8070A FOREIGN KEY (program_id) REFERENCES program (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_project_validated_search DROP FOREIGN KEY FK_31ADE7DF32C8A3DE');
        $this->addSql('ALTER TABLE log_project_validated_search DROP FOREIGN KEY FK_31ADE7DF77570A4C');
        $this->addSql('ALTER TABLE log_project_validated_search ADD CONSTRAINT FK_31ADE7DF32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_project_validated_search ADD CONSTRAINT FK_31ADE7DF77570A4C FOREIGN KEY (perimeter_id) REFERENCES perimeter (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_public_project_search DROP FOREIGN KEY FK_427BA97632C8A3DE');
        $this->addSql('ALTER TABLE log_public_project_search DROP FOREIGN KEY FK_427BA97677570A4C');
        $this->addSql('ALTER TABLE log_public_project_search ADD CONSTRAINT FK_427BA97632C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_public_project_search ADD CONSTRAINT FK_427BA97677570A4C FOREIGN KEY (perimeter_id) REFERENCES perimeter (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_public_project_view DROP FOREIGN KEY FK_721B9DA232C8A3DE');
        $this->addSql('ALTER TABLE log_public_project_view ADD CONSTRAINT FK_721B9DA232C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE log_aid_createds_folder DROP FOREIGN KEY FK_874772CE32C8A3DE');
        $this->addSql('ALTER TABLE log_aid_createds_folder ADD CONSTRAINT FK_874772CE32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE log_aid_eligibility_test DROP FOREIGN KEY FK_7D453FD1D81AE339');
        $this->addSql('ALTER TABLE log_aid_eligibility_test ADD CONSTRAINT FK_7D453FD1D81AE339 FOREIGN KEY (eligibility_test_id) REFERENCES eligibility_test (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE log_aid_search DROP FOREIGN KEY FK_A377D16777570A4C');
        $this->addSql('ALTER TABLE log_aid_search DROP FOREIGN KEY FK_A377D16732C8A3DE');
        $this->addSql('ALTER TABLE log_aid_search ADD CONSTRAINT FK_A377D16777570A4C FOREIGN KEY (perimeter_id) REFERENCES perimeter (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE log_aid_search ADD CONSTRAINT FK_A377D16732C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE log_aid_view DROP FOREIGN KEY FK_FF81DC6132C8A3DE');
        $this->addSql('ALTER TABLE log_aid_view ADD CONSTRAINT FK_FF81DC6132C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE log_backer_view DROP FOREIGN KEY FK_13526F7F59543840');
        $this->addSql('ALTER TABLE log_backer_view DROP FOREIGN KEY FK_13526F7F32C8A3DE');
        $this->addSql('ALTER TABLE log_backer_view ADD CONSTRAINT FK_13526F7F59543840 FOREIGN KEY (backer_id) REFERENCES backer (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE log_backer_view ADD CONSTRAINT FK_13526F7F32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE log_blog_post_view DROP FOREIGN KEY FK_748A159C32C8A3DE');
        $this->addSql('ALTER TABLE log_blog_post_view DROP FOREIGN KEY FK_748A159CA77FBEAF');
        $this->addSql('ALTER TABLE log_blog_post_view ADD CONSTRAINT FK_748A159C32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE log_blog_post_view ADD CONSTRAINT FK_748A159CA77FBEAF FOREIGN KEY (blog_post_id) REFERENCES blog_post (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE log_blog_promotion_post_click DROP FOREIGN KEY FK_15E9548D5AE9B170');
        $this->addSql('ALTER TABLE log_blog_promotion_post_click ADD CONSTRAINT FK_15E9548D5AE9B170 FOREIGN KEY (blog_promotion_post_id) REFERENCES blog_promotion_post (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE log_blog_promotion_post_display DROP FOREIGN KEY FK_E1429695AE9B170');
        $this->addSql('ALTER TABLE log_blog_promotion_post_display ADD CONSTRAINT FK_E1429695AE9B170 FOREIGN KEY (blog_promotion_post_id) REFERENCES blog_promotion_post (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE log_program_view DROP FOREIGN KEY FK_2B4A6C2F32C8A3DE');
        $this->addSql('ALTER TABLE log_program_view DROP FOREIGN KEY FK_2B4A6C2F3EB8070A');
        $this->addSql('ALTER TABLE log_program_view ADD CONSTRAINT FK_2B4A6C2F32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE log_program_view ADD CONSTRAINT FK_2B4A6C2F3EB8070A FOREIGN KEY (program_id) REFERENCES program (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE log_project_validated_search DROP FOREIGN KEY FK_31ADE7DF32C8A3DE');
        $this->addSql('ALTER TABLE log_project_validated_search DROP FOREIGN KEY FK_31ADE7DF77570A4C');
        $this->addSql('ALTER TABLE log_project_validated_search ADD CONSTRAINT FK_31ADE7DF32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE log_project_validated_search ADD CONSTRAINT FK_31ADE7DF77570A4C FOREIGN KEY (perimeter_id) REFERENCES perimeter (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE log_public_project_search DROP FOREIGN KEY FK_427BA97632C8A3DE');
        $this->addSql('ALTER TABLE log_public_project_search DROP FOREIGN KEY FK_427BA97677570A4C');
        $this->addSql('ALTER TABLE log_public_project_search ADD CONSTRAINT FK_427BA97632C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE log_public_project_search ADD CONSTRAINT FK_427BA97677570A4C FOREIGN KEY (perimeter_id) REFERENCES perimeter (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE log_public_project_view DROP FOREIGN KEY FK_721B9DA232C8A3DE');
        $this->addSql('ALTER TABLE log_public_project_view ADD CONSTRAINT FK_721B9DA232C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
