<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210184747 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial migration for Exercise and Workout entities';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE exercise (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, duree INT NOT NULL, description VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workout (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, niveau VARCHAR(50) NOT NULL, objectif LONGTEXT NOT NULL COMMENT \'(DC2Type:simple_array)\', duree INT NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workout_exercise (workout_id INT NOT NULL, exercise_id INT NOT NULL, INDEX IDX_4C3E6EADA6CCCFC9 (workout_id), INDEX IDX_4C3E6EADE934951A (exercise_id), PRIMARY KEY(workout_id, exercise_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE workout_exercise ADD CONSTRAINT FK_4C3E6EADA6CCCFC9 FOREIGN KEY (workout_id) REFERENCES workout (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workout_exercise ADD CONSTRAINT FK_4C3E6EADE934951A FOREIGN KEY (exercise_id) REFERENCES exercise (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workout_exercise DROP FOREIGN KEY FK_4C3E6EADA6CCCFC9');
        $this->addSql('ALTER TABLE workout_exercise DROP FOREIGN KEY FK_4C3E6EADE934951A');
        $this->addSql('DROP TABLE exercise');
        $this->addSql('DROP TABLE workout');
        $this->addSql('DROP TABLE workout_exercise');
    }
}
