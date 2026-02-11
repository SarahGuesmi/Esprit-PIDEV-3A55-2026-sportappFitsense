<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211155832 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE workout_objectif (workout_id INT NOT NULL, objectif_sportif_id INT NOT NULL, INDEX IDX_3A8985E7A6CCCFC9 (workout_id), INDEX IDX_3A8985E7B27FDBD (objectif_sportif_id), PRIMARY KEY(workout_id, objectif_sportif_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE workout_objectif ADD CONSTRAINT FK_3A8985E7A6CCCFC9 FOREIGN KEY (workout_id) REFERENCES workout (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workout_objectif ADD CONSTRAINT FK_3A8985E7B27FDBD FOREIGN KEY (objectif_sportif_id) REFERENCES objectif_sportif (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE etat_mental ADD CONSTRAINT FK_5662EE37A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE workout DROP objectif');
        $this->addSql('ALTER TABLE workout_exercise RENAME INDEX idx_4c3e6eada6cccfc9 TO IDX_76AB38AAA6CCCFC9');
        $this->addSql('ALTER TABLE workout_exercise RENAME INDEX idx_4c3e6eade934951a TO IDX_76AB38AAE934951A');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workout_objectif DROP FOREIGN KEY FK_3A8985E7A6CCCFC9');
        $this->addSql('ALTER TABLE workout_objectif DROP FOREIGN KEY FK_3A8985E7B27FDBD');
        $this->addSql('DROP TABLE workout_objectif');
        $this->addSql('ALTER TABLE workout ADD objectif LONGTEXT NOT NULL COMMENT \'(DC2Type:simple_array)\'');
        $this->addSql('ALTER TABLE etat_mental DROP FOREIGN KEY FK_5662EE37A76ED395');
        $this->addSql('ALTER TABLE workout_exercise RENAME INDEX idx_76ab38aaa6cccfc9 TO IDX_4C3E6EADA6CCCFC9');
        $this->addSql('ALTER TABLE workout_exercise RENAME INDEX idx_76ab38aae934951a TO IDX_4C3E6EADE934951A');
    }
}
