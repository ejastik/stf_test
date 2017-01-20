<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170119155802 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE testmediabundle_tag (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, deletedAt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE testmediabundle_image_tag (id SERIAL NOT NULL, image_id INT DEFAULT NULL, tag_id INT DEFAULT NULL, deletedAt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_EBF185A83DA5256D ON testmediabundle_image_tag (image_id)');
        $this->addSql('CREATE INDEX IDX_EBF185A8BAD26311 ON testmediabundle_image_tag (tag_id)');
        $this->addSql('CREATE TABLE testmediabundle_image (id SERIAL NOT NULL, url VARCHAR(255) DEFAULT \'image\' NOT NULL, purpose VARCHAR(255) DEFAULT NULL, image_type VARCHAR(255) DEFAULT NULL, deletedAt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE testmediabundle_image_tag ADD CONSTRAINT FK_EBF185A83DA5256D FOREIGN KEY (image_id) REFERENCES testmediabundle_image (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE testmediabundle_image_tag ADD CONSTRAINT FK_EBF185A8BAD26311 FOREIGN KEY (tag_id) REFERENCES testmediabundle_tag (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE testmediabundle_image_tag DROP CONSTRAINT FK_EBF185A8BAD26311');
        $this->addSql('ALTER TABLE testmediabundle_image_tag DROP CONSTRAINT FK_EBF185A83DA5256D');
        $this->addSql('DROP TABLE testmediabundle_tag');
        $this->addSql('DROP TABLE testmediabundle_image_tag');
        $this->addSql('DROP TABLE testmediabundle_image');
    }
}
