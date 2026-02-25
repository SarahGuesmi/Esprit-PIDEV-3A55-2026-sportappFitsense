<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209231332 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE objectif_sportif (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, profile_physique_id INT NOT NULL, INDEX IDX_2CC45BE146CDA1E6 (profile_physique_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE profile_physique (id INT AUTO_INCREMENT NOT NULL, weight DOUBLE PRECISION DEFAULT NULL, height DOUBLE PRECISION DEFAULT NULL, gender VARCHAR(10) DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_66934956A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE objectif_sportif ADD CONSTRAINT FK_2CC45BE146CDA1E6 FOREIGN KEY (profile_physique_id) REFERENCES profile_physique (id)');
        $this->addSql('ALTER TABLE profile_physique ADD CONSTRAINT FK_66934956A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user CHANGE account_status account_status VARCHAR(50) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE objectif_sportif DROP FOREIGN KEY FK_2CC45BE146CDA1E6');
        $this->addSql('ALTER TABLE profile_physique DROP FOREIGN KEY FK_66934956A76ED395');
        $this->addSql('DROP TABLE objectif_sportif');
        $this->addSql('DROP TABLE profile_physique');
        $this->addSql('ALTER TABLE user CHANGE account_status account_status VARCHAR(20) NOT NULL');
    }
}
