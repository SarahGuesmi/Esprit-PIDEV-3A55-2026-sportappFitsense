<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211171031 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, message VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, is_read TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, related_user_id INT DEFAULT NULL, INDEX IDX_BF5476CA98771930 (related_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA98771930 FOREIGN KEY (related_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE workout DROP objectif');
        $this->addSql('ALTER TABLE workout_exercise RENAME INDEX idx_4c3e6eada6cccfc9 TO IDX_76AB38AAA6CCCFC9');
        $this->addSql('ALTER TABLE workout_exercise RENAME INDEX idx_4c3e6eade934951a TO IDX_76AB38AAE934951A');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA98771930');
        $this->addSql('DROP TABLE notification');
        $this->addSql('ALTER TABLE workout ADD objectif LONGTEXT NOT NULL COMMENT \'(DC2Type:simple_array)\'');
        $this->addSql('ALTER TABLE workout_exercise RENAME INDEX idx_76ab38aaa6cccfc9 TO IDX_4C3E6EADA6CCCFC9');
        $this->addSql('ALTER TABLE workout_exercise RENAME INDEX idx_76ab38aae934951a TO IDX_4C3E6EADE934951A');
    }
}
