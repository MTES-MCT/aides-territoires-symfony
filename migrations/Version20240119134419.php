<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240119134419 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE blog_promotion_post_keyword_reference (blog_promotion_post_id INT NOT NULL, keyword_reference_id INT NOT NULL, INDEX IDX_1FF774755AE9B170 (blog_promotion_post_id), INDEX IDX_1FF77475FD82A2C3 (keyword_reference_id), PRIMARY KEY(blog_promotion_post_id, keyword_reference_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE blog_promotion_post_keyword_reference ADD CONSTRAINT FK_1FF774755AE9B170 FOREIGN KEY (blog_promotion_post_id) REFERENCES blog_promotion_post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE blog_promotion_post_keyword_reference ADD CONSTRAINT FK_1FF77475FD82A2C3 FOREIGN KEY (keyword_reference_id) REFERENCES keyword_reference (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE blog_promotion_post DROP FOREIGN KEY FK_A8074FABE5E1AC8D');
        $this->addSql('DROP INDEX IDX_A8074FABE5E1AC8D ON blog_promotion_post');
        $this->addSql('ALTER TABLE blog_promotion_post DROP keyword_references_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE blog_promotion_post_keyword_reference DROP FOREIGN KEY FK_1FF774755AE9B170');
        $this->addSql('ALTER TABLE blog_promotion_post_keyword_reference DROP FOREIGN KEY FK_1FF77475FD82A2C3');
        $this->addSql('DROP TABLE blog_promotion_post_keyword_reference');
        $this->addSql('ALTER TABLE blog_promotion_post ADD keyword_references_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE blog_promotion_post ADD CONSTRAINT FK_A8074FABE5E1AC8D FOREIGN KEY (keyword_references_id) REFERENCES keyword_reference (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_A8074FABE5E1AC8D ON blog_promotion_post (keyword_references_id)');
    }
}
