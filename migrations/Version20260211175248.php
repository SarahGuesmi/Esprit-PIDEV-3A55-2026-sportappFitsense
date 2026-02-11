<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211175248 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE questionnaire (id INT AUTO_INCREMENT NOT NULL, note_globale INT DEFAULT NULL, satisfaction INT DEFAULT NULL, intensite VARCHAR(255) DEFAULT NULL, exercices_compris VARCHAR(255) DEFAULT NULL, duree VARCHAR(255) DEFAULT NULL, ressenti_physique VARCHAR(255) DEFAULT NULL, stress VARCHAR(255) DEFAULT NULL, motivation VARCHAR(255) DEFAULT NULL, progression VARCHAR(255) DEFAULT NULL, rapproche_objectifs INT DEFAULT NULL, commentaire LONGTEXT DEFAULT NULL, date_soumission DATETIME NOT NULL, user_name VARCHAR(255) DEFAULT NULL, user_id INT NOT NULL, workout_id INT DEFAULT NULL, INDEX IDX_7A64DAFA76ED395 (user_id), INDEX IDX_7A64DAFA6CCCFC9 (workout_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE questionnaire ADD CONSTRAINT FK_7A64DAFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE questionnaire ADD CONSTRAINT FK_7A64DAFA6CCCFC9 FOREIGN KEY (workout_id) REFERENCES workout (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE questionnaire DROP FOREIGN KEY FK_7A64DAFA76ED395');
        $this->addSql('ALTER TABLE questionnaire DROP FOREIGN KEY FK_7A64DAFA6CCCFC9');
        $this->addSql('DROP TABLE questionnaire');
    }
}
