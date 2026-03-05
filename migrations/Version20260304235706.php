<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260304235706 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_FAB3FC16CD53EDB6');
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_FAB3FC16F624B39D');
        $this->addSql('ALTER TABLE daily_nutrition DROP FOREIGN KEY FK_36C779EBA76ED395');
        $this->addSql('ALTER TABLE etat_mental DROP FOREIGN KEY FK_5662EE37A76ED395');
        $this->addSql('ALTER TABLE feedback_response DROP FOREIGN KEY FK_7135A0893C105691');
        $this->addSql('ALTER TABLE feedback_response DROP FOREIGN KEY FK_7135A089A76ED395');
        $this->addSql('ALTER TABLE login_attempt DROP FOREIGN KEY FK_8C11C1BA76ED395');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA98771930');
        $this->addSql('ALTER TABLE passkey_credential DROP FOREIGN KEY FK_PASSKEY_USER_ID');
        $this->addSql('ALTER TABLE profile_physique DROP FOREIGN KEY FK_66934956A76ED395');
        $this->addSql('ALTER TABLE questionnaire DROP FOREIGN KEY FK_7A64DAF3C105691');
        $this->addSql('ALTER TABLE questionnaire DROP FOREIGN KEY FK_7A64DAFA76ED395');
        $this->addSql('ALTER TABLE recette_consommee DROP FOREIGN KEY FK_D8B6A0C5A76ED395');
        $this->addSql('ALTER TABLE recette_nutritionnelle DROP FOREIGN KEY FK_E4AA46C33C105691');
        $this->addSql('ALTER TABLE recipe_favorites DROP FOREIGN KEY FK_F3A84E13A76ED395');
        $this->addSql('ALTER TABLE recommendation DROP FOREIGN KEY FK_433224D23C105691');
        $this->addSql('ALTER TABLE recommendation DROP FOREIGN KEY FK_433224D2A76ED395');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE user_exercise_progress DROP FOREIGN KEY FK_DC72E985A76ED395');
        $this->addSql('ALTER TABLE workout DROP FOREIGN KEY FK_649FFB723C105691');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL, account_status VARCHAR(50) NOT NULL, date_creation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', google_authenticator_secret VARCHAR(255) DEFAULT NULL, photo VARCHAR(255) DEFAULT NULL, username VARCHAR(255) DEFAULT NULL, email_email VARCHAR(180) NOT NULL, name_firstname VARCHAR(255) NOT NULL, name_lastname VARCHAR(255) NOT NULL, phone_number VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_1483A5E9F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('DROP TABLE user');
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_FAB3FC16CD53EDB6');
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_FAB3FC16F624B39D');
        $this->addSql('ALTER TABLE chat_message ADD created_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT FK_FAB3FC16B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT FK_FAB3FC16896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT FK_FAB3FC16CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT FK_FAB3FC16F624B39D FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_FAB3FC16B03A8386 ON chat_message (created_by_id)');
        $this->addSql('CREATE INDEX IDX_FAB3FC16896DBBDE ON chat_message (updated_by_id)');
        $this->addSql('ALTER TABLE daily_nutrition DROP FOREIGN KEY FK_36C779EBA76ED395');
        $this->addSql('ALTER TABLE daily_nutrition ADD CONSTRAINT FK_36C779EBA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE etat_mental DROP FOREIGN KEY FK_5662EE37A76ED395');
        $this->addSql('ALTER TABLE etat_mental ADD created_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE etat_mental ADD CONSTRAINT FK_5662EE37B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE etat_mental ADD CONSTRAINT FK_5662EE37896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE etat_mental ADD CONSTRAINT FK_5662EE37A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_5662EE37B03A8386 ON etat_mental (created_by_id)');
        $this->addSql('CREATE INDEX IDX_5662EE37896DBBDE ON etat_mental (updated_by_id)');
        $this->addSql('ALTER TABLE feedback_response DROP FOREIGN KEY FK_7135A0893C105691');
        $this->addSql('ALTER TABLE feedback_response DROP FOREIGN KEY FK_7135A089A76ED395');
        $this->addSql('ALTER TABLE feedback_response ADD created_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE feedback_response ADD CONSTRAINT FK_7135A089B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE feedback_response ADD CONSTRAINT FK_7135A089896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE feedback_response ADD CONSTRAINT FK_7135A0893C105691 FOREIGN KEY (coach_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE feedback_response ADD CONSTRAINT FK_7135A089A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_7135A089B03A8386 ON feedback_response (created_by_id)');
        $this->addSql('CREATE INDEX IDX_7135A089896DBBDE ON feedback_response (updated_by_id)');
        $this->addSql('ALTER TABLE login_attempt DROP FOREIGN KEY FK_8C11C1BA76ED395');
        $this->addSql('ALTER TABLE login_attempt CHANGE email email_email VARCHAR(180) NOT NULL');
        $this->addSql('ALTER TABLE login_attempt ADD CONSTRAINT FK_8C11C1BA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA98771930');
        $this->addSql('ALTER TABLE notification ADD created_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA98771930 FOREIGN KEY (related_user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_BF5476CAB03A8386 ON notification (created_by_id)');
        $this->addSql('CREATE INDEX IDX_BF5476CA896DBBDE ON notification (updated_by_id)');
        $this->addSql('ALTER TABLE objectif_sportif DROP FOREIGN KEY FK_2CC45BE146CDA1E6');
        $this->addSql('ALTER TABLE objectif_sportif ADD CONSTRAINT FK_2CC45BE146CDA1E6 FOREIGN KEY (profile_physique_id) REFERENCES profile_physique (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE passkey_credential ADD created_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE passkey_credential ADD CONSTRAINT FK_DFD64A45A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE passkey_credential ADD CONSTRAINT FK_DFD64A45B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE passkey_credential ADD CONSTRAINT FK_DFD64A45896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_DFD64A45B03A8386 ON passkey_credential (created_by_id)');
        $this->addSql('CREATE INDEX IDX_DFD64A45896DBBDE ON passkey_credential (updated_by_id)');
        $this->addSql('ALTER TABLE profile_physique DROP FOREIGN KEY FK_66934956A76ED395');
        $this->addSql('ALTER TABLE profile_physique ADD CONSTRAINT FK_66934956A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE questionnaire DROP FOREIGN KEY FK_7A64DAF3C105691');
        $this->addSql('ALTER TABLE questionnaire DROP FOREIGN KEY FK_7A64DAFA76ED395');
        $this->addSql('ALTER TABLE questionnaire ADD CONSTRAINT FK_7A64DAF3C105691 FOREIGN KEY (coach_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE questionnaire ADD CONSTRAINT FK_7A64DAFA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recette_consommee DROP FOREIGN KEY FK_D8B6A0C5A76ED395');
        $this->addSql('ALTER TABLE recette_consommee ADD CONSTRAINT FK_D8B6A0C5A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recette_nutritionnelle DROP FOREIGN KEY FK_E4AA46C33C105691');
        $this->addSql('ALTER TABLE recette_nutritionnelle ADD created_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE recette_nutritionnelle ADD CONSTRAINT FK_E4AA46C3B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE recette_nutritionnelle ADD CONSTRAINT FK_E4AA46C3896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE recette_nutritionnelle ADD CONSTRAINT FK_E4AA46C33C105691 FOREIGN KEY (coach_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_E4AA46C3B03A8386 ON recette_nutritionnelle (created_by_id)');
        $this->addSql('CREATE INDEX IDX_E4AA46C3896DBBDE ON recette_nutritionnelle (updated_by_id)');
        $this->addSql('ALTER TABLE recipe_favorites DROP FOREIGN KEY FK_F3A84E13A76ED395');
        $this->addSql('ALTER TABLE recipe_favorites ADD CONSTRAINT FK_F3A84E13A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recommendation DROP FOREIGN KEY FK_433224D23C105691');
        $this->addSql('ALTER TABLE recommendation DROP FOREIGN KEY FK_433224D2A76ED395');
        $this->addSql('ALTER TABLE recommendation ADD created_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE recommendation ADD CONSTRAINT FK_433224D2B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE recommendation ADD CONSTRAINT FK_433224D2896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE recommendation ADD CONSTRAINT FK_433224D23C105691 FOREIGN KEY (coach_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recommendation ADD CONSTRAINT FK_433224D2A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_433224D2B03A8386 ON recommendation (created_by_id)');
        $this->addSql('CREATE INDEX IDX_433224D2896DBBDE ON recommendation (updated_by_id)');
        $this->addSql('ALTER TABLE recommended_exercise DROP FOREIGN KEY FK_216D5218D173940B');
        $this->addSql('ALTER TABLE recommended_exercise ADD CONSTRAINT FK_216D5218D173940B FOREIGN KEY (recommendation_id) REFERENCES recommendation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_exercise_progress DROP FOREIGN KEY FK_DC72E985A76ED395');
        $this->addSql('ALTER TABLE user_exercise_progress ADD created_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user_exercise_progress ADD CONSTRAINT FK_DC72E985B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user_exercise_progress ADD CONSTRAINT FK_DC72E985896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user_exercise_progress ADD CONSTRAINT FK_DC72E985A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_DC72E985B03A8386 ON user_exercise_progress (created_by_id)');
        $this->addSql('CREATE INDEX IDX_DC72E985896DBBDE ON user_exercise_progress (updated_by_id)');
        $this->addSql('ALTER TABLE workout DROP FOREIGN KEY FK_649FFB723C105691');
        $this->addSql('ALTER TABLE workout ADD CONSTRAINT FK_649FFB723C105691 FOREIGN KEY (coach_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_FAB3FC16F624B39D');
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_FAB3FC16CD53EDB6');
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_FAB3FC16B03A8386');
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_FAB3FC16896DBBDE');
        $this->addSql('ALTER TABLE daily_nutrition DROP FOREIGN KEY FK_36C779EBA76ED395');
        $this->addSql('ALTER TABLE etat_mental DROP FOREIGN KEY FK_5662EE37A76ED395');
        $this->addSql('ALTER TABLE etat_mental DROP FOREIGN KEY FK_5662EE37B03A8386');
        $this->addSql('ALTER TABLE etat_mental DROP FOREIGN KEY FK_5662EE37896DBBDE');
        $this->addSql('ALTER TABLE feedback_response DROP FOREIGN KEY FK_7135A089A76ED395');
        $this->addSql('ALTER TABLE feedback_response DROP FOREIGN KEY FK_7135A0893C105691');
        $this->addSql('ALTER TABLE feedback_response DROP FOREIGN KEY FK_7135A089B03A8386');
        $this->addSql('ALTER TABLE feedback_response DROP FOREIGN KEY FK_7135A089896DBBDE');
        $this->addSql('ALTER TABLE login_attempt DROP FOREIGN KEY FK_8C11C1BA76ED395');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA98771930');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAB03A8386');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA896DBBDE');
        $this->addSql('ALTER TABLE passkey_credential DROP FOREIGN KEY FK_DFD64A45A76ED395');
        $this->addSql('ALTER TABLE passkey_credential DROP FOREIGN KEY FK_DFD64A45B03A8386');
        $this->addSql('ALTER TABLE passkey_credential DROP FOREIGN KEY FK_DFD64A45896DBBDE');
        $this->addSql('ALTER TABLE profile_physique DROP FOREIGN KEY FK_66934956A76ED395');
        $this->addSql('ALTER TABLE questionnaire DROP FOREIGN KEY FK_7A64DAFA76ED395');
        $this->addSql('ALTER TABLE questionnaire DROP FOREIGN KEY FK_7A64DAF3C105691');
        $this->addSql('ALTER TABLE recette_consommee DROP FOREIGN KEY FK_D8B6A0C5A76ED395');
        $this->addSql('ALTER TABLE recette_nutritionnelle DROP FOREIGN KEY FK_E4AA46C33C105691');
        $this->addSql('ALTER TABLE recette_nutritionnelle DROP FOREIGN KEY FK_E4AA46C3B03A8386');
        $this->addSql('ALTER TABLE recette_nutritionnelle DROP FOREIGN KEY FK_E4AA46C3896DBBDE');
        $this->addSql('ALTER TABLE recipe_favorites DROP FOREIGN KEY FK_F3A84E13A76ED395');
        $this->addSql('ALTER TABLE recommendation DROP FOREIGN KEY FK_433224D23C105691');
        $this->addSql('ALTER TABLE recommendation DROP FOREIGN KEY FK_433224D2A76ED395');
        $this->addSql('ALTER TABLE recommendation DROP FOREIGN KEY FK_433224D2B03A8386');
        $this->addSql('ALTER TABLE recommendation DROP FOREIGN KEY FK_433224D2896DBBDE');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE user_exercise_progress DROP FOREIGN KEY FK_DC72E985A76ED395');
        $this->addSql('ALTER TABLE user_exercise_progress DROP FOREIGN KEY FK_DC72E985B03A8386');
        $this->addSql('ALTER TABLE user_exercise_progress DROP FOREIGN KEY FK_DC72E985896DBBDE');
        $this->addSql('ALTER TABLE workout DROP FOREIGN KEY FK_649FFB723C105691');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, firstname VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, lastname VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, email VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, roles JSON NOT NULL, password VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, account_status VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, date_creation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', google_authenticator_secret VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, phone VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, photo VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, username VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE users');
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_FAB3FC16F624B39D');
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_FAB3FC16CD53EDB6');
        $this->addSql('DROP INDEX IDX_FAB3FC16B03A8386 ON chat_message');
        $this->addSql('DROP INDEX IDX_FAB3FC16896DBBDE ON chat_message');
        $this->addSql('ALTER TABLE chat_message DROP created_by_id, DROP updated_by_id, DROP updated_at');
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT FK_FAB3FC16F624B39D FOREIGN KEY (sender_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT FK_FAB3FC16CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE daily_nutrition DROP FOREIGN KEY FK_36C779EBA76ED395');
        $this->addSql('ALTER TABLE daily_nutrition ADD CONSTRAINT FK_36C779EBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE etat_mental DROP FOREIGN KEY FK_5662EE37A76ED395');
        $this->addSql('DROP INDEX IDX_5662EE37B03A8386 ON etat_mental');
        $this->addSql('DROP INDEX IDX_5662EE37896DBBDE ON etat_mental');
        $this->addSql('ALTER TABLE etat_mental DROP created_by_id, DROP updated_by_id, DROP updated_at, CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE etat_mental ADD CONSTRAINT FK_5662EE37A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE feedback_response DROP FOREIGN KEY FK_7135A089A76ED395');
        $this->addSql('ALTER TABLE feedback_response DROP FOREIGN KEY FK_7135A0893C105691');
        $this->addSql('DROP INDEX IDX_7135A089B03A8386 ON feedback_response');
        $this->addSql('DROP INDEX IDX_7135A089896DBBDE ON feedback_response');
        $this->addSql('ALTER TABLE feedback_response DROP created_by_id, DROP updated_by_id, DROP updated_at');
        $this->addSql('ALTER TABLE feedback_response ADD CONSTRAINT FK_7135A089A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE feedback_response ADD CONSTRAINT FK_7135A0893C105691 FOREIGN KEY (coach_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE login_attempt DROP FOREIGN KEY FK_8C11C1BA76ED395');
        $this->addSql('ALTER TABLE login_attempt CHANGE email_email email VARCHAR(180) NOT NULL');
        $this->addSql('ALTER TABLE login_attempt ADD CONSTRAINT FK_8C11C1BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA98771930');
        $this->addSql('DROP INDEX IDX_BF5476CAB03A8386 ON notification');
        $this->addSql('DROP INDEX IDX_BF5476CA896DBBDE ON notification');
        $this->addSql('ALTER TABLE notification DROP created_by_id, DROP updated_by_id, DROP updated_at');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA98771930 FOREIGN KEY (related_user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE objectif_sportif DROP FOREIGN KEY FK_2CC45BE146CDA1E6');
        $this->addSql('ALTER TABLE objectif_sportif ADD CONSTRAINT FK_2CC45BE146CDA1E6 FOREIGN KEY (profile_physique_id) REFERENCES profile_physique (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('DROP INDEX IDX_DFD64A45B03A8386 ON passkey_credential');
        $this->addSql('DROP INDEX IDX_DFD64A45896DBBDE ON passkey_credential');
        $this->addSql('ALTER TABLE passkey_credential DROP created_by_id, DROP updated_by_id, DROP updated_at');
        $this->addSql('ALTER TABLE passkey_credential ADD CONSTRAINT FK_PASSKEY_USER_ID FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE profile_physique DROP FOREIGN KEY FK_66934956A76ED395');
        $this->addSql('ALTER TABLE profile_physique ADD CONSTRAINT FK_66934956A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE questionnaire DROP FOREIGN KEY FK_7A64DAFA76ED395');
        $this->addSql('ALTER TABLE questionnaire DROP FOREIGN KEY FK_7A64DAF3C105691');
        $this->addSql('ALTER TABLE questionnaire ADD CONSTRAINT FK_7A64DAFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE questionnaire ADD CONSTRAINT FK_7A64DAF3C105691 FOREIGN KEY (coach_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recette_consommee DROP FOREIGN KEY FK_D8B6A0C5A76ED395');
        $this->addSql('ALTER TABLE recette_consommee ADD CONSTRAINT FK_D8B6A0C5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE recette_nutritionnelle DROP FOREIGN KEY FK_E4AA46C33C105691');
        $this->addSql('DROP INDEX IDX_E4AA46C3B03A8386 ON recette_nutritionnelle');
        $this->addSql('DROP INDEX IDX_E4AA46C3896DBBDE ON recette_nutritionnelle');
        $this->addSql('ALTER TABLE recette_nutritionnelle DROP created_by_id, DROP updated_by_id, DROP updated_at');
        $this->addSql('ALTER TABLE recette_nutritionnelle ADD CONSTRAINT FK_E4AA46C33C105691 FOREIGN KEY (coach_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE recipe_favorites DROP FOREIGN KEY FK_F3A84E13A76ED395');
        $this->addSql('ALTER TABLE recipe_favorites ADD CONSTRAINT FK_F3A84E13A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recommendation DROP FOREIGN KEY FK_433224D23C105691');
        $this->addSql('ALTER TABLE recommendation DROP FOREIGN KEY FK_433224D2A76ED395');
        $this->addSql('DROP INDEX IDX_433224D2B03A8386 ON recommendation');
        $this->addSql('DROP INDEX IDX_433224D2896DBBDE ON recommendation');
        $this->addSql('ALTER TABLE recommendation DROP created_by_id, DROP updated_by_id, DROP updated_at');
        $this->addSql('ALTER TABLE recommendation ADD CONSTRAINT FK_433224D23C105691 FOREIGN KEY (coach_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recommendation ADD CONSTRAINT FK_433224D2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recommended_exercise DROP FOREIGN KEY FK_216D5218D173940B');
        $this->addSql('ALTER TABLE recommended_exercise ADD CONSTRAINT FK_216D5218D173940B FOREIGN KEY (recommendation_id) REFERENCES recommendation (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_exercise_progress DROP FOREIGN KEY FK_DC72E985A76ED395');
        $this->addSql('DROP INDEX IDX_DC72E985B03A8386 ON user_exercise_progress');
        $this->addSql('DROP INDEX IDX_DC72E985896DBBDE ON user_exercise_progress');
        $this->addSql('ALTER TABLE user_exercise_progress DROP created_by_id, DROP updated_by_id, DROP updated_at');
        $this->addSql('ALTER TABLE user_exercise_progress ADD CONSTRAINT FK_DC72E985A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workout DROP FOREIGN KEY FK_649FFB723C105691');
        $this->addSql('ALTER TABLE workout ADD CONSTRAINT FK_649FFB723C105691 FOREIGN KEY (coach_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
