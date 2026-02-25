<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260225140647 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE daily_nutrition (id INT AUTO_INCREMENT NOT NULL, day_date DATE NOT NULL, calories INT NOT NULL, water_ml INT NOT NULL, calories_goal INT NOT NULL, water_goal INT NOT NULL, over_goal_alert_shown TINYINT(1) NOT NULL, user_id INT NOT NULL, INDEX IDX_36C779EBA76ED395 (user_id), UNIQUE INDEX uniq_user_day (user_id, day_date), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE recipe_favorites (recette_nutritionnelle_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_F3A84E1365884FB8 (recette_nutritionnelle_id), INDEX IDX_F3A84E13A76ED395 (user_id), PRIMARY KEY(recette_nutritionnelle_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE daily_nutrition ADD CONSTRAINT FK_36C779EBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE recipe_favorites ADD CONSTRAINT FK_F3A84E1365884FB8 FOREIGN KEY (recette_nutritionnelle_id) REFERENCES recette_nutritionnelle (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recipe_favorites ADD CONSTRAINT FK_F3A84E13A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE users');
        $this->addSql('ALTER TABLE etat_mental DROP description');
        $this->addSql('DROP INDEX UNIQ_8D93D649F85E0677 ON user');
        $this->addSql('ALTER TABLE user DROP username');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, firstname VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, lastname VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, email VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, roles JSON NOT NULL, password VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, account_status VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, date_creation DATETIME NOT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE daily_nutrition DROP FOREIGN KEY FK_36C779EBA76ED395');
        $this->addSql('ALTER TABLE recipe_favorites DROP FOREIGN KEY FK_F3A84E1365884FB8');
        $this->addSql('ALTER TABLE recipe_favorites DROP FOREIGN KEY FK_F3A84E13A76ED395');
        $this->addSql('DROP TABLE daily_nutrition');
        $this->addSql('DROP TABLE recipe_favorites');
        $this->addSql('ALTER TABLE etat_mental ADD description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD username VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON user (username)');
    }
}
