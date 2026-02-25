<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop unused user profile columns: bio, avatar, show_email, show_phone, show_bio.';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('user');
        $platform = $this->connection->getDatabasePlatform();
        foreach (['bio', 'avatar', 'show_email', 'show_phone', 'show_bio'] as $column) {
            if ($table->hasColumn($column)) {
                $this->addSql('ALTER TABLE user DROP ' . $platform->quoteIdentifier($column));
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD bio LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD avatar VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD show_email TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE user ADD show_phone TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE user ADD show_bio TINYINT(1) DEFAULT 1 NOT NULL');
    }
}
