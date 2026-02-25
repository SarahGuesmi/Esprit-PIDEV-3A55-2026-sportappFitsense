<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211212740 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE questionnaire ADD titre VARCHAR(255) DEFAULT NULL, ADD options JSON DEFAULT NULL, ADD type VARCHAR(20) DEFAULT \'response\' NOT NULL, ADD coach_id INT DEFAULT NULL, CHANGE date_soumission date_soumission DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE questionnaire ADD CONSTRAINT FK_7A64DAF3C105691 FOREIGN KEY (coach_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_7A64DAF3C105691 ON questionnaire (coach_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE questionnaire DROP FOREIGN KEY FK_7A64DAF3C105691');
        $this->addSql('DROP INDEX IDX_7A64DAF3C105691 ON questionnaire');
        $this->addSql('ALTER TABLE questionnaire DROP titre, DROP options, DROP type, DROP coach_id, CHANGE date_soumission date_soumission DATETIME NOT NULL');
    }
}
