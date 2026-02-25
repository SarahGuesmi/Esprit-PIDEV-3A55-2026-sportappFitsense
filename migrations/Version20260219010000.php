<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260219010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add coach field to workout table';
    }

    public function up(Schema $schema): void
    {
        // Add coach_id column to workout table
        $this->addSql('ALTER TABLE workout ADD coach_id INT NULL');
        $this->addSql('ALTER TABLE workout ADD CONSTRAINT FK_57F0A7E7C6F3198F FOREIGN KEY (coach_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_57F0A7E7C6F3198F ON workout (coach_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workout DROP FOREIGN KEY FK_57F0A7E7C6F3198F');
        $this->addSql('DROP INDEX IDX_57F0A7E7C6F3198F ON workout');
        $this->addSql('ALTER TABLE workout DROP coach_id');
    }
}
