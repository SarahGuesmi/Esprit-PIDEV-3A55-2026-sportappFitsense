<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260221210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add deleted_by_sender_at and deleted_by_receiver_at to chat_message for per-user conversation delete (single table).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE chat_message ADD deleted_by_sender_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE chat_message ADD deleted_by_receiver_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE chat_message DROP deleted_by_sender_at');
        $this->addSql('ALTER TABLE chat_message DROP deleted_by_receiver_at');
    }
}
