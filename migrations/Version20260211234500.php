<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211234500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Updated Questionnaire entity: changed Workout from ManyToOne to ManyToMany and made User nullable.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE questionnaire_workout (questionnaire_id INT NOT NULL, workout_id INT NOT NULL, INDEX IDX_F8C8A3DBCE07E8FF (questionnaire_id), INDEX IDX_F8C8A3DBA6CCCFC9 (workout_id), PRIMARY KEY(questionnaire_id, workout_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE questionnaire_workout ADD CONSTRAINT FK_F8C8A3DBCE07E8FF FOREIGN KEY (questionnaire_id) REFERENCES questionnaire (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE questionnaire_workout ADD CONSTRAINT FK_F8C8A3DBA6CCCFC9 FOREIGN KEY (workout_id) REFERENCES workout (id) ON DELETE CASCADE');
        
        // Remove the many-to-one relationship column
        $this->addSql('ALTER TABLE questionnaire DROP FOREIGN KEY FK_7A64DAFA6CCCFC9');
        $this->addSql('DROP INDEX IDX_7A64DAFA6CCCFC9 ON questionnaire');
        $this->addSql('ALTER TABLE questionnaire DROP workout_id, CHANGE user_id user_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE questionnaire_workout DROP FOREIGN KEY FK_F8C8A3DBCE07E8FF');
        $this->addSql('ALTER TABLE questionnaire_workout DROP FOREIGN KEY FK_F8C8A3DBA6CCCFC9');
        $this->addSql('DROP TABLE questionnaire_workout');
        
        // Restore the many-to-one relationship column
        $this->addSql('ALTER TABLE questionnaire ADD workout_id INT DEFAULT NULL, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE questionnaire ADD CONSTRAINT FK_7A64DAFA6CCCFC9 FOREIGN KEY (workout_id) REFERENCES workout (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_7A64DAFA6CCCFC9 ON questionnaire (workout_id)');
    }
}
