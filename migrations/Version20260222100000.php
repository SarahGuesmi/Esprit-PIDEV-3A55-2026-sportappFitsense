<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user profile fields: phone, bio, photo, avatar, privacy (show_email, show_phone, show_bio).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD phone VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD bio LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD photo VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD avatar VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD show_email TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE user ADD show_phone TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE user ADD show_bio TINYINT(1) DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP phone');
        $this->addSql('ALTER TABLE user DROP bio');
        $this->addSql('ALTER TABLE user DROP photo');
        $this->addSql('ALTER TABLE user DROP avatar');
        $this->addSql('ALTER TABLE user DROP show_email');
        $this->addSql('ALTER TABLE user DROP show_phone');
        $this->addSql('ALTER TABLE user DROP show_bio');
    }
}
