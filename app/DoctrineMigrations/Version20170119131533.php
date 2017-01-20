<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170119131533 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE pagebundle_page_translation DROP CONSTRAINT fk_d3baaa87c4663e4');
        $this->addSql('DROP SEQUENCE platformbundle_config_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE filebundle_filerotator_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE emailbundle_email_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE pagebundle_page_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE pagebundle_page_translation_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE logbundle_log_receiver_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE logbundle_log_id_seq CASCADE');
        $this->addSql('DROP TABLE emailbundle_email');
        $this->addSql('DROP TABLE filebundle_filerotator');
        $this->addSql('DROP TABLE logbundle_log_receiver');
        $this->addSql('DROP TABLE logbundle_log');
        $this->addSql('DROP TABLE platformbundle_config');
        $this->addSql('DROP TABLE pagebundle_page');
        $this->addSql('DROP TABLE pagebundle_page_translation');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SEQUENCE platformbundle_config_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE filebundle_filerotator_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE emailbundle_email_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE pagebundle_page_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE pagebundle_page_translation_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE logbundle_log_receiver_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE logbundle_log_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE emailbundle_email (id SERIAL NOT NULL, alias VARCHAR(255) NOT NULL, subject VARCHAR(255) NOT NULL, maket VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_34b70796e16c6b94 ON emailbundle_email (alias)');
        $this->addSql('CREATE TABLE filebundle_filerotator (id SERIAL NOT NULL, name VARCHAR(255) DEFAULT NULL, file_types VARCHAR(255) DEFAULT NULL, source_dir VARCHAR(255) NOT NULL, destination_dir VARCHAR(255) DEFAULT NULL, destination_sub_dir_mask VARCHAR(255) DEFAULT NULL, destination_dir_mode VARCHAR(255) DEFAULT NULL, destination_file_mode VARCHAR(255) DEFAULT NULL, destination_file_prefix_mask VARCHAR(255) DEFAULT NULL, destination_file_name_mask VARCHAR(255) DEFAULT NULL, destination_file_suffix_mask VARCHAR(255) DEFAULT NULL, age INT NOT NULL, make_archive BOOLEAN DEFAULT \'false\', PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE logbundle_log_receiver (id SERIAL NOT NULL, name VARCHAR(255) DEFAULT NULL, email VARCHAR(255) NOT NULL, enabled BOOLEAN DEFAULT \'true\' NOT NULL, dictionary_logs BOOLEAN DEFAULT \'true\' NOT NULL, remainders_logs BOOLEAN DEFAULT \'true\', status_logs BOOLEAN DEFAULT \'true\' NOT NULL, pharmacy_connection_logs BOOLEAN DEFAULT \'true\' NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, deletedat TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE logbundle_log (id SERIAL NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, type VARCHAR(255) NOT NULL, message TEXT NOT NULL, sent BOOLEAN DEFAULT \'false\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE platformbundle_config (id SERIAL NOT NULL, updatedby_id INT DEFAULT NULL, param VARCHAR(255) NOT NULL, value VARCHAR(255) DEFAULT NULL, user_role_sections TEXT DEFAULT \'a:0:{}\' NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_87b3fe4da4fa7c89 ON platformbundle_config (param)');
        $this->addSql('CREATE INDEX idx_87b3fe4d65ff1aec ON platformbundle_config (updatedby_id)');
        $this->addSql('COMMENT ON COLUMN platformbundle_config.user_role_sections IS \'(DC2Type:array)\'');
        $this->addSql('CREATE TABLE pagebundle_page (id SERIAL NOT NULL, slug VARCHAR(255) NOT NULL, meta_title VARCHAR(255) DEFAULT NULL, meta_keywords VARCHAR(255) DEFAULT NULL, meta_description TEXT DEFAULT NULL, text TEXT DEFAULT NULL, deletedat TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX page_slug_unique ON pagebundle_page (slug) WHERE (deletedat IS NULL)');
        $this->addSql('CREATE TABLE pagebundle_page_translation (id SERIAL NOT NULL, page_id INT DEFAULT NULL, locale_id INT DEFAULT NULL, html TEXT DEFAULT NULL, css TEXT DEFAULT NULL, js TEXT DEFAULT NULL, meta_title VARCHAR(255) DEFAULT NULL, meta_keywords VARCHAR(255) DEFAULT NULL, meta_description TEXT DEFAULT NULL, deletedat TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_d3baaa87e559dfd1 ON pagebundle_page_translation (locale_id)');
        $this->addSql('CREATE UNIQUE INDEX page_locale_unique ON pagebundle_page_translation (page_id, locale_id)');
        $this->addSql('CREATE INDEX idx_d3baaa87c4663e4 ON pagebundle_page_translation (page_id)');
        $this->addSql('ALTER TABLE platformbundle_config ADD CONSTRAINT fk_87b3fe4d65ff1aec FOREIGN KEY (updatedby_id) REFERENCES fos_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pagebundle_page_translation ADD CONSTRAINT fk_d3baaa87c4663e4 FOREIGN KEY (page_id) REFERENCES pagebundle_page (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pagebundle_page_translation ADD CONSTRAINT fk_d3baaa87e559dfd1 FOREIGN KEY (locale_id) REFERENCES translationbundle_locale (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
