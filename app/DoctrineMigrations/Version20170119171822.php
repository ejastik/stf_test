<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170119171822 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE userbundle_userinfo DROP CONSTRAINT fk_f327fad185dfe78e');
        $this->addSql('DROP SEQUENCE userbundle_userinfo_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE userbundle_userrole_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE mediabundle_image_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE translationbundle_locale_id_seq CASCADE');
        $this->addSql('DROP TABLE mediabundle_image');
        $this->addSql('DROP TABLE translationbundle_locale');
        $this->addSql('DROP TABLE userbundle_userrole');
        $this->addSql('DROP TABLE userbundle_userinfo');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SEQUENCE userbundle_userinfo_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE userbundle_userrole_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE mediabundle_image_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE translationbundle_locale_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE mediabundle_image (id SERIAL NOT NULL, url VARCHAR(255) DEFAULT \'image\' NOT NULL, description VARCHAR(255) DEFAULT NULL, crop_x INT DEFAULT NULL, crop_y INT DEFAULT NULL, crop_width INT DEFAULT NULL, crop_as_main BOOLEAN DEFAULT NULL, purpose VARCHAR(255) DEFAULT NULL, alt TEXT DEFAULT NULL, link TEXT DEFAULT NULL, image_type VARCHAR(255) DEFAULT NULL, deletedat TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE translationbundle_locale (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE userbundle_userrole (id INT NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, dashboard VARCHAR(1) DEFAULT \'N\' NOT NULL, nomenclature VARCHAR(1) DEFAULT \'N\' NOT NULL, pharmacies VARCHAR(1) DEFAULT \'N\' NOT NULL, clients VARCHAR(1) DEFAULT \'N\' NOT NULL, usermanagement VARCHAR(1) DEFAULT \'N\' NOT NULL, config VARCHAR(1) DEFAULT \'N\' NOT NULL, orders VARCHAR(1) DEFAULT \'N\' NOT NULL, reports VARCHAR(1) DEFAULT \'N\' NOT NULL, chiefoperator BOOLEAN DEFAULT \'false\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE userbundle_userinfo (id INT NOT NULL, user_id INT DEFAULT NULL, userrole_id INT DEFAULT NULL, firstname VARCHAR(255) DEFAULT NULL, lastname VARCHAR(255) DEFAULT NULL, middlename VARCHAR(255) DEFAULT NULL, avatar VARCHAR(255) DEFAULT NULL, gravatar BOOLEAN DEFAULT \'false\', email VARCHAR(255) DEFAULT NULL, tel VARCHAR(255) DEFAULT NULL, birthdate TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, "position" VARCHAR(255) DEFAULT NULL, comment TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_f327fad1a76ed395 ON userbundle_userinfo (user_id)');
        $this->addSql('CREATE INDEX idx_f327fad185dfe78e ON userbundle_userinfo (userrole_id)');
        $this->addSql('ALTER TABLE userbundle_userinfo ADD CONSTRAINT fk_f327fad1a76ed395 FOREIGN KEY (user_id) REFERENCES fos_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE userbundle_userinfo ADD CONSTRAINT fk_f327fad185dfe78e FOREIGN KEY (userrole_id) REFERENCES userbundle_userrole (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
