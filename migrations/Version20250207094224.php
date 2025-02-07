<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250207094224 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ab_test (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, date_create DATE NOT NULL, date_start DATE DEFAULT NULL, date_end DATE DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ab_test_user (id INT AUTO_INCREMENT NOT NULL, ab_test_id INT NOT NULL, user_id INT DEFAULT NULL, date_create DATE NOT NULL, version VARCHAR(50) NOT NULL, INDEX IDX_E666FC1CA00D9457 (ab_test_id), INDEX IDX_E666FC1CA76ED395 (user_id), INDEX version_ab_test_user (version), INDEX date_create_ab_test_user (date_create), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ab_test_user ADD CONSTRAINT FK_E666FC1CA00D9457 FOREIGN KEY (ab_test_id) REFERENCES ab_test (id)');
        $this->addSql('ALTER TABLE ab_test_user ADD CONSTRAINT FK_E666FC1CA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ab_test_user DROP FOREIGN KEY FK_E666FC1CA00D9457');
        $this->addSql('ALTER TABLE ab_test_user DROP FOREIGN KEY FK_E666FC1CA76ED395');
        $this->addSql('DROP TABLE ab_test');
        $this->addSql('DROP TABLE ab_test_user');
    }
}
