<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:chat-add-conversation-deleted-columns',
    description: 'Add deleted_by_sender_at and deleted_by_receiver_at to chat_message (and drop user_conversation_deleted if exists).',
)]
class ChatAddConversationDeletedColumnsCommand extends Command
{
    public function __construct(
        private Connection $connection
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $schemaManager = $this->connection->createSchemaManager();
            $columns = $schemaManager->listTableColumns('chat_message');
            $hasSender = false;
            $hasReceiver = false;
            foreach ($columns as $col) {
                $name = strtolower($col->getName());
                if ($name === 'deleted_by_sender_at') {
                    $hasSender = true;
                }
                if ($name === 'deleted_by_receiver_at') {
                    $hasReceiver = true;
                }
            }

            if (!$hasSender) {
                $io->writeln('Adding deleted_by_sender_at to chat_message...');
                $this->connection->executeStatement(
                    "ALTER TABLE chat_message ADD deleted_by_sender_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'"
                );
            }
            if (!$hasReceiver) {
                $io->writeln('Adding deleted_by_receiver_at to chat_message...');
                $this->connection->executeStatement(
                    "ALTER TABLE chat_message ADD deleted_by_receiver_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'"
                );
            }
            if ($hasSender && $hasReceiver) {
                $io->writeln('Columns already exist on chat_message.');
            }

            if ($schemaManager->tablesExist(['user_conversation_deleted'])) {
                $io->writeln('Dropping old table user_conversation_deleted...');
                $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
                $this->connection->executeStatement('DROP TABLE IF EXISTS user_conversation_deleted');
                $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
                $io->writeln('Dropped user_conversation_deleted.');
            }

            $io->success('Done. Conversation delete now uses chat_message only.');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('Failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
