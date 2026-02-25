<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260221180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add receiver_id to chat_message for conversation-based chat (admin-coach, coach-athlete).';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('chat_message');

        if (!$table->hasColumn('receiver_id')) {
            $this->addSql('ALTER TABLE chat_message ADD receiver_id INT DEFAULT NULL');
            $this->addSql('UPDATE chat_message SET receiver_id = sender_id WHERE receiver_id IS NULL');
            $this->addSql('ALTER TABLE chat_message MODIFY receiver_id INT NOT NULL');
            $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT FK_3A6C2B83CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES user (id)');
            $this->addSql('CREATE INDEX IDX_3A6C2B83CD53EDB6 ON chat_message (receiver_id)');
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('chat_message');
        if ($table->hasColumn('receiver_id')) {
            $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_3A6C2B83CD53EDB6');
            $this->addSql('DROP INDEX IDX_3A6C2B83CD53EDB6 ON chat_message');
            $this->addSql('ALTER TABLE chat_message DROP receiver_id');
        }
    }
}
