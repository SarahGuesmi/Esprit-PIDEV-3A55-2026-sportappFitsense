<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211223752 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE recette_consommee (id INT AUTO_INCREMENT NOT NULL, date_consommation DATETIME NOT NULL, image VARCHAR(255) DEFAULT NULL, kcal INT NOT NULL, proteins INT NOT NULL, user_id INT NOT NULL, recette_id INT NOT NULL, INDEX IDX_D8B6A0C5A76ED395 (user_id), INDEX IDX_D8B6A0C589312FE9 (recette_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE recette_nutritionnelle (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, kcal INT DEFAULT NULL, proteins INT DEFAULT NULL, type_meal VARCHAR(20) DEFAULT NULL, ingredients LONGTEXT DEFAULT NULL, preparation LONGTEXT DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, objectifs JSON DEFAULT NULL, coach_id INT NOT NULL, INDEX IDX_E4AA46C33C105691 (coach_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE recette_consommee ADD CONSTRAINT FK_D8B6A0C5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE recette_consommee ADD CONSTRAINT FK_D8B6A0C589312FE9 FOREIGN KEY (recette_id) REFERENCES recette_nutritionnelle (id)');
        $this->addSql('ALTER TABLE recette_nutritionnelle ADD CONSTRAINT FK_E4AA46C33C105691 FOREIGN KEY (coach_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE recette_consommee DROP FOREIGN KEY FK_D8B6A0C5A76ED395');
        $this->addSql('ALTER TABLE recette_consommee DROP FOREIGN KEY FK_D8B6A0C589312FE9');
        $this->addSql('ALTER TABLE recette_nutritionnelle DROP FOREIGN KEY FK_E4AA46C33C105691');
        $this->addSql('DROP TABLE recette_consommee');
        $this->addSql('DROP TABLE recette_nutritionnelle');
    }
}
