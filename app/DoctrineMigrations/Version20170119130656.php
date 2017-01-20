<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170119130656 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE automailer_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE restbundle_authcode_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE restbundle_client_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE restbundle_refreshtoken_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE restbundle_accesstoken_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE userbundle_userinfo_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE userbundle_userrole_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE fos_user_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE automailer (id INT NOT NULL, from_email VARCHAR(255) NOT NULL, from_name VARCHAR(255) NOT NULL, to_email VARCHAR(255) NOT NULL, subject TEXT NOT NULL, body TEXT NOT NULL, alt_body TEXT NOT NULL, swift_message TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, sent_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, started_sending_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, is_html BOOLEAN NOT NULL, is_sending BOOLEAN DEFAULT NULL, is_sent BOOLEAN DEFAULT NULL, is_failed BOOLEAN DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX find_next ON automailer (is_sent, is_failed, is_sending, created_at)');
        $this->addSql('CREATE INDEX recover_sending ON automailer (is_sending, started_sending_at)');
        $this->addSql('CREATE TABLE platformbundle_config (id SERIAL NOT NULL, param VARCHAR(255) NOT NULL, value VARCHAR(255) DEFAULT NULL, user_role_sections TEXT DEFAULT \'a:0:{}\' NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, updatedBy_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_87B3FE4DA4FA7C89 ON platformbundle_config (param)');
        $this->addSql('CREATE INDEX IDX_87B3FE4D65FF1AEC ON platformbundle_config (updatedBy_id)');
        $this->addSql('COMMENT ON COLUMN platformbundle_config.user_role_sections IS \'(DC2Type:array)\'');
        $this->addSql('CREATE TABLE emailbundle_email (id SERIAL NOT NULL, alias VARCHAR(255) NOT NULL, subject VARCHAR(255) NOT NULL, maket VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_34B70796E16C6B94 ON emailbundle_email (alias)');
        $this->addSql('CREATE TABLE filebundle_filerotator (id SERIAL NOT NULL, name VARCHAR(255) DEFAULT NULL, file_types VARCHAR(255) DEFAULT NULL, source_dir VARCHAR(255) NOT NULL, destination_dir VARCHAR(255) DEFAULT NULL, destination_sub_dir_mask VARCHAR(255) DEFAULT NULL, destination_dir_mode VARCHAR(255) DEFAULT NULL, destination_file_mode VARCHAR(255) DEFAULT NULL, destination_file_prefix_mask VARCHAR(255) DEFAULT NULL, destination_file_name_mask VARCHAR(255) DEFAULT NULL, destination_file_suffix_mask VARCHAR(255) DEFAULT NULL, age INT NOT NULL, make_archive BOOLEAN DEFAULT \'false\', PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE mediabundle_image (id SERIAL NOT NULL, url VARCHAR(255) DEFAULT \'image\' NOT NULL, description VARCHAR(255) DEFAULT NULL, crop_x INT DEFAULT NULL, crop_y INT DEFAULT NULL, crop_width INT DEFAULT NULL, crop_as_main BOOLEAN DEFAULT NULL, purpose VARCHAR(255) DEFAULT NULL, alt TEXT DEFAULT NULL, link TEXT DEFAULT NULL, image_type VARCHAR(255) DEFAULT NULL, deletedAt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE pagebundle_page (id SERIAL NOT NULL, slug VARCHAR(255) NOT NULL, meta_title VARCHAR(255) DEFAULT NULL, meta_keywords VARCHAR(255) DEFAULT NULL, meta_description TEXT DEFAULT NULL, text TEXT DEFAULT NULL, deletedAt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX page_slug_unique ON pagebundle_page (slug) WHERE deletedAt IS NULL');
        $this->addSql('CREATE TABLE pagebundle_page_translation (id SERIAL NOT NULL, page_id INT DEFAULT NULL, locale_id INT DEFAULT NULL, html TEXT DEFAULT NULL, css TEXT DEFAULT NULL, js TEXT DEFAULT NULL, meta_title VARCHAR(255) DEFAULT NULL, meta_keywords VARCHAR(255) DEFAULT NULL, meta_description TEXT DEFAULT NULL, deletedAt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D3BAAA87C4663E4 ON pagebundle_page_translation (page_id)');
        $this->addSql('CREATE INDEX IDX_D3BAAA87E559DFD1 ON pagebundle_page_translation (locale_id)');
        $this->addSql('CREATE UNIQUE INDEX page_locale_unique ON pagebundle_page_translation (page_id, locale_id)');
        $this->addSql('CREATE TABLE restbundle_authcode (id INT NOT NULL, client_id INT NOT NULL, user_id INT DEFAULT NULL, token VARCHAR(255) NOT NULL, redirect_uri TEXT NOT NULL, expires_at INT DEFAULT NULL, scope VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BF5583AF5F37A13B ON restbundle_authcode (token)');
        $this->addSql('CREATE INDEX IDX_BF5583AF19EB6921 ON restbundle_authcode (client_id)');
        $this->addSql('CREATE INDEX IDX_BF5583AFA76ED395 ON restbundle_authcode (user_id)');
        $this->addSql('CREATE TABLE restbundle_client (id INT NOT NULL, random_id VARCHAR(255) NOT NULL, redirect_uris TEXT NOT NULL, secret VARCHAR(255) NOT NULL, allowed_grant_types TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN restbundle_client.redirect_uris IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN restbundle_client.allowed_grant_types IS \'(DC2Type:array)\'');
        $this->addSql('CREATE TABLE restbundle_views_count (id SERIAL NOT NULL, client VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, content_id INT NOT NULL, timestamp TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, ip VARCHAR(16) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE restbundle_twitter_token (id SERIAL NOT NULL, oauth_token VARCHAR(255) NOT NULL, oauth_token_secret VARCHAR(255) NOT NULL, expired_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE restbundle_refreshtoken (id INT NOT NULL, client_id INT NOT NULL, user_id INT DEFAULT NULL, token VARCHAR(255) NOT NULL, expires_at INT DEFAULT NULL, scope VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5DFC873A5F37A13B ON restbundle_refreshtoken (token)');
        $this->addSql('CREATE INDEX IDX_5DFC873A19EB6921 ON restbundle_refreshtoken (client_id)');
        $this->addSql('CREATE INDEX IDX_5DFC873AA76ED395 ON restbundle_refreshtoken (user_id)');
        $this->addSql('CREATE TABLE restbundle_accesstoken (id INT NOT NULL, client_id INT NOT NULL, user_id INT DEFAULT NULL, token VARCHAR(255) NOT NULL, expires_at INT DEFAULT NULL, scope VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B54BB8715F37A13B ON restbundle_accesstoken (token)');
        $this->addSql('CREATE INDEX IDX_B54BB87119EB6921 ON restbundle_accesstoken (client_id)');
        $this->addSql('CREATE INDEX IDX_B54BB871A76ED395 ON restbundle_accesstoken (user_id)');
        $this->addSql('CREATE TABLE translationbundle_locale (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE logbundle_log_receiver (id SERIAL NOT NULL, name VARCHAR(255) DEFAULT NULL, email VARCHAR(255) NOT NULL, enabled BOOLEAN DEFAULT \'true\' NOT NULL, dictionary_logs BOOLEAN DEFAULT \'true\' NOT NULL, remainders_logs BOOLEAN DEFAULT \'true\', status_logs BOOLEAN DEFAULT \'true\' NOT NULL, pharmacy_connection_logs BOOLEAN DEFAULT \'true\' NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, deletedAt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE logbundle_log (id SERIAL NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, type VARCHAR(255) NOT NULL, message TEXT NOT NULL, sent BOOLEAN DEFAULT \'false\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE userbundle_userinfo (id INT NOT NULL, user_id INT DEFAULT NULL, firstName VARCHAR(255) DEFAULT NULL, lastName VARCHAR(255) DEFAULT NULL, middleName VARCHAR(255) DEFAULT NULL, avatar VARCHAR(255) DEFAULT NULL, gravatar BOOLEAN DEFAULT \'false\', email VARCHAR(255) DEFAULT NULL, tel VARCHAR(255) DEFAULT NULL, birthDate TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, position VARCHAR(255) DEFAULT NULL, comment TEXT DEFAULT NULL, userRole_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F327FAD1A76ED395 ON userbundle_userinfo (user_id)');
        $this->addSql('CREATE INDEX IDX_F327FAD185DFE78E ON userbundle_userinfo (userRole_id)');
        $this->addSql('CREATE TABLE userbundle_userrole (id INT NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, dashboard VARCHAR(1) DEFAULT \'N\' NOT NULL, nomenclature VARCHAR(1) DEFAULT \'N\' NOT NULL, pharmacies VARCHAR(1) DEFAULT \'N\' NOT NULL, clients VARCHAR(1) DEFAULT \'N\' NOT NULL, userManagement VARCHAR(1) DEFAULT \'N\' NOT NULL, config VARCHAR(1) DEFAULT \'N\' NOT NULL, orders VARCHAR(1) DEFAULT \'N\' NOT NULL, reports VARCHAR(1) DEFAULT \'N\' NOT NULL, chiefOperator BOOLEAN DEFAULT \'false\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE fos_group (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, roles TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4B019DDB5E237E06 ON fos_group (name)');
        $this->addSql('COMMENT ON COLUMN fos_group.roles IS \'(DC2Type:array)\'');
        $this->addSql('CREATE TABLE fos_user (id INT NOT NULL, username VARCHAR(255) NOT NULL, username_canonical VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, email_canonical VARCHAR(255) NOT NULL, enabled BOOLEAN NOT NULL, salt VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, last_login TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, locked BOOLEAN NOT NULL, expired BOOLEAN NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, confirmation_token VARCHAR(255) DEFAULT NULL, password_requested_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, roles TEXT NOT NULL, credentials_expired BOOLEAN NOT NULL, credentials_expire_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, confirmRequestedAt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_957A647992FC23A8 ON fos_user (username_canonical)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_957A6479A0D96FBF ON fos_user (email_canonical)');
        $this->addSql('COMMENT ON COLUMN fos_user.roles IS \'(DC2Type:array)\'');
        $this->addSql('CREATE TABLE fos_user_group (user_id INT NOT NULL, group_id INT NOT NULL, PRIMARY KEY(user_id, group_id))');
        $this->addSql('CREATE INDEX IDX_583D1F3EA76ED395 ON fos_user_group (user_id)');
        $this->addSql('CREATE INDEX IDX_583D1F3EFE54D947 ON fos_user_group (group_id)');
        $this->addSql('ALTER TABLE platformbundle_config ADD CONSTRAINT FK_87B3FE4D65FF1AEC FOREIGN KEY (updatedBy_id) REFERENCES fos_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pagebundle_page_translation ADD CONSTRAINT FK_D3BAAA87C4663E4 FOREIGN KEY (page_id) REFERENCES pagebundle_page (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pagebundle_page_translation ADD CONSTRAINT FK_D3BAAA87E559DFD1 FOREIGN KEY (locale_id) REFERENCES translationbundle_locale (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE restbundle_authcode ADD CONSTRAINT FK_BF5583AF19EB6921 FOREIGN KEY (client_id) REFERENCES restbundle_client (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE restbundle_authcode ADD CONSTRAINT FK_BF5583AFA76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE restbundle_refreshtoken ADD CONSTRAINT FK_5DFC873A19EB6921 FOREIGN KEY (client_id) REFERENCES restbundle_client (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE restbundle_refreshtoken ADD CONSTRAINT FK_5DFC873AA76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE restbundle_accesstoken ADD CONSTRAINT FK_B54BB87119EB6921 FOREIGN KEY (client_id) REFERENCES restbundle_client (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE restbundle_accesstoken ADD CONSTRAINT FK_B54BB871A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE userbundle_userinfo ADD CONSTRAINT FK_F327FAD1A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE userbundle_userinfo ADD CONSTRAINT FK_F327FAD185DFE78E FOREIGN KEY (userRole_id) REFERENCES userbundle_userrole (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE fos_user_group ADD CONSTRAINT FK_583D1F3EA76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE fos_user_group ADD CONSTRAINT FK_583D1F3EFE54D947 FOREIGN KEY (group_id) REFERENCES fos_group (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE pagebundle_page_translation DROP CONSTRAINT FK_D3BAAA87C4663E4');
        $this->addSql('ALTER TABLE restbundle_authcode DROP CONSTRAINT FK_BF5583AF19EB6921');
        $this->addSql('ALTER TABLE restbundle_refreshtoken DROP CONSTRAINT FK_5DFC873A19EB6921');
        $this->addSql('ALTER TABLE restbundle_accesstoken DROP CONSTRAINT FK_B54BB87119EB6921');
        $this->addSql('ALTER TABLE pagebundle_page_translation DROP CONSTRAINT FK_D3BAAA87E559DFD1');
        $this->addSql('ALTER TABLE userbundle_userinfo DROP CONSTRAINT FK_F327FAD185DFE78E');
        $this->addSql('ALTER TABLE fos_user_group DROP CONSTRAINT FK_583D1F3EFE54D947');
        $this->addSql('ALTER TABLE platformbundle_config DROP CONSTRAINT FK_87B3FE4D65FF1AEC');
        $this->addSql('ALTER TABLE restbundle_authcode DROP CONSTRAINT FK_BF5583AFA76ED395');
        $this->addSql('ALTER TABLE restbundle_refreshtoken DROP CONSTRAINT FK_5DFC873AA76ED395');
        $this->addSql('ALTER TABLE restbundle_accesstoken DROP CONSTRAINT FK_B54BB871A76ED395');
        $this->addSql('ALTER TABLE userbundle_userinfo DROP CONSTRAINT FK_F327FAD1A76ED395');
        $this->addSql('ALTER TABLE fos_user_group DROP CONSTRAINT FK_583D1F3EA76ED395');
        $this->addSql('DROP SEQUENCE automailer_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE restbundle_authcode_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE restbundle_client_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE restbundle_refreshtoken_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE restbundle_accesstoken_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE userbundle_userinfo_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE userbundle_userrole_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE fos_user_id_seq CASCADE');
        $this->addSql('DROP TABLE automailer');
        $this->addSql('DROP TABLE platformbundle_config');
        $this->addSql('DROP TABLE emailbundle_email');
        $this->addSql('DROP TABLE filebundle_filerotator');
        $this->addSql('DROP TABLE mediabundle_image');
        $this->addSql('DROP TABLE pagebundle_page');
        $this->addSql('DROP TABLE pagebundle_page_translation');
        $this->addSql('DROP TABLE restbundle_authcode');
        $this->addSql('DROP TABLE restbundle_client');
        $this->addSql('DROP TABLE restbundle_views_count');
        $this->addSql('DROP TABLE restbundle_twitter_token');
        $this->addSql('DROP TABLE restbundle_refreshtoken');
        $this->addSql('DROP TABLE restbundle_accesstoken');
        $this->addSql('DROP TABLE translationbundle_locale');
        $this->addSql('DROP TABLE logbundle_log_receiver');
        $this->addSql('DROP TABLE logbundle_log');
        $this->addSql('DROP TABLE userbundle_userinfo');
        $this->addSql('DROP TABLE userbundle_userrole');
        $this->addSql('DROP TABLE fos_group');
        $this->addSql('DROP TABLE fos_user');
        $this->addSql('DROP TABLE fos_user_group');
    }
}
