<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Increase user.phone column length from 50 to 255.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user MODIFY phone VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user MODIFY phone VARCHAR(50) DEFAULT NULL');
    }
}
