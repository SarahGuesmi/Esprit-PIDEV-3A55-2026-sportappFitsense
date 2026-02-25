<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260219000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create feedback_response table for storing user workout feedback';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE feedback_response (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, workout_id INT NOT NULL, rating VARCHAR(50) NOT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', coach_id INT DEFAULT NULL, INDEX IDX_1B3215E6A76ED395 (user_id), INDEX IDX_1B3215E61FB354CD (workout_id), INDEX IDX_1B3215E6E415E15B (coach_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE feedback_response ADD CONSTRAINT FK_1B3215E6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE feedback_response ADD CONSTRAINT FK_1B3215E61FB354CD FOREIGN KEY (workout_id) REFERENCES workout (id)');
        $this->addSql('ALTER TABLE feedback_response ADD CONSTRAINT FK_1B3215E6E415E15B FOREIGN KEY (coach_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE feedback_response');
    }
}
