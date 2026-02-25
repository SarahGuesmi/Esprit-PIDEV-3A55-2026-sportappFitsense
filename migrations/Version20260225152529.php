<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260225152529 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE daily_nutrition DROP FOREIGN KEY FK_36C779EBA76ED395');
        $this->addSql('ALTER TABLE recipe_favorites DROP FOREIGN KEY FK_F3A84E1365884FB8');
        $this->addSql('ALTER TABLE recipe_favorites DROP FOREIGN KEY FK_F3A84E13A76ED395');
        $this->addSql('DROP TABLE daily_nutrition');
        $this->addSql('DROP TABLE recipe_favorites');
        $this->addSql('ALTER TABLE etat_mental ADD description LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE daily_nutrition (id INT AUTO_INCREMENT NOT NULL, day_date DATE NOT NULL, calories INT NOT NULL, water_ml INT NOT NULL, calories_goal INT NOT NULL, water_goal INT NOT NULL, over_goal_alert_shown TINYINT(1) NOT NULL, user_id INT NOT NULL, UNIQUE INDEX uniq_user_day (user_id, day_date), INDEX IDX_36C779EBA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE recipe_favorites (recette_nutritionnelle_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_F3A84E13A76ED395 (user_id), INDEX IDX_F3A84E1365884FB8 (recette_nutritionnelle_id), PRIMARY KEY(recette_nutritionnelle_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE daily_nutrition ADD CONSTRAINT FK_36C779EBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE recipe_favorites ADD CONSTRAINT FK_F3A84E1365884FB8 FOREIGN KEY (recette_nutritionnelle_id) REFERENCES recette_nutritionnelle (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recipe_favorites ADD CONSTRAINT FK_F3A84E13A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE etat_mental DROP description');
    }
}
