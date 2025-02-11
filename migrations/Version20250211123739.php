<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250211123739 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ab_test (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, ratio INT NOT NULL, date_start DATE DEFAULT NULL, date_end DATE DEFAULT NULL, hour_start INT DEFAULT NULL, hour_end INT DEFAULT NULL, date_create DATE NOT NULL, INDEX name_ab_test (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ab_test_user (id INT AUTO_INCREMENT NOT NULL, ab_test_id INT NOT NULL, user_id INT DEFAULT NULL, variation VARCHAR(50) NOT NULL, date_create DATE NOT NULL, INDEX IDX_E666FC1CA00D9457 (ab_test_id), INDEX IDX_E666FC1CA76ED395 (user_id), INDEX variation_ab_test_user (variation), INDEX date_create_ab_test_user (date_create), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ab_test_vote (id INT AUTO_INCREMENT NOT NULL, ab_test_id INT NOT NULL, aid_id INT DEFAULT NULL, vote INT NOT NULL, php_session_id VARCHAR(255) NOT NULL, variation VARCHAR(255) NOT NULL, data VARCHAR(255) DEFAULT NULL, date_create DATE NOT NULL, INDEX IDX_31E5AF31A00D9457 (ab_test_id), INDEX IDX_31E5AF31CB0C1416 (aid_id), INDEX variation_ab_test_vote (variation), INDEX date_create_ab_test_vote (date_create), INDEX php_session_id_ab_test_vote (php_session_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ab_test_user ADD CONSTRAINT FK_E666FC1CA00D9457 FOREIGN KEY (ab_test_id) REFERENCES ab_test (id)');
        $this->addSql('ALTER TABLE ab_test_user ADD CONSTRAINT FK_E666FC1CA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE ab_test_vote ADD CONSTRAINT FK_31E5AF31A00D9457 FOREIGN KEY (ab_test_id) REFERENCES ab_test (id)');
        $this->addSql('ALTER TABLE ab_test_vote ADD CONSTRAINT FK_31E5AF31CB0C1416 FOREIGN KEY (aid_id) REFERENCES aid (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ab_test_user DROP FOREIGN KEY FK_E666FC1CA00D9457');
        $this->addSql('ALTER TABLE ab_test_user DROP FOREIGN KEY FK_E666FC1CA76ED395');
        $this->addSql('ALTER TABLE ab_test_vote DROP FOREIGN KEY FK_31E5AF31A00D9457');
        $this->addSql('ALTER TABLE ab_test_vote DROP FOREIGN KEY FK_31E5AF31CB0C1416');
        $this->addSql('DROP TABLE ab_test');
        $this->addSql('DROP TABLE ab_test_user');
        $this->addSql('DROP TABLE ab_test_vote');
    }
}
