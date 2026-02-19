<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260218222824 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE recommendation (id INT AUTO_INCREMENT NOT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, coach_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_433224D23C105691 (coach_id), INDEX IDX_433224D2A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE recommended_exercise (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, duration INT DEFAULT NULL, recommendation_id INT NOT NULL, INDEX IDX_216D5218D173940B (recommendation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE recommendation ADD CONSTRAINT FK_433224D23C105691 FOREIGN KEY (coach_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE recommendation ADD CONSTRAINT FK_433224D2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE recommended_exercise ADD CONSTRAINT FK_216D5218D173940B FOREIGN KEY (recommendation_id) REFERENCES recommendation (id)');
        $this->addSql('ALTER TABLE questionnaire ADD CONSTRAINT FK_7A64DAF3C105691 FOREIGN KEY (coach_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE questionnaire_workout DROP FOREIGN KEY FK_F8C8A3DBA6CCCFC9');
        $this->addSql('ALTER TABLE questionnaire_workout DROP FOREIGN KEY FK_F8C8A3DBCE07E8FF');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE recommendation DROP FOREIGN KEY FK_433224D23C105691');
        $this->addSql('ALTER TABLE recommendation DROP FOREIGN KEY FK_433224D2A76ED395');
        $this->addSql('ALTER TABLE recommended_exercise DROP FOREIGN KEY FK_216D5218D173940B');
        $this->addSql('DROP TABLE recommendation');
        $this->addSql('DROP TABLE recommended_exercise');
        $this->addSql('ALTER TABLE questionnaire DROP FOREIGN KEY FK_7A64DAF3C105691');
    }
}
