<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:chat-add-receiver-column',
    description: 'Add receiver_id column to chat_message table if missing (fix for chat conversations).',
)]
class ChatAddReceiverColumnCommand extends Command
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
            $hasReceiver = false;
            foreach ($columns as $col) {
                if (strtolower($col->getName()) === 'receiver_id') {
                    $hasReceiver = true;
                    break;
                }
            }

            if ($hasReceiver) {
                $io->success('Column receiver_id already exists on chat_message. Nothing to do.');
                return Command::SUCCESS;
            }

            $io->writeln('Adding receiver_id to chat_message...');

            $this->connection->executeStatement('ALTER TABLE chat_message ADD receiver_id INT DEFAULT NULL');
            $this->connection->executeStatement('UPDATE chat_message SET receiver_id = sender_id WHERE receiver_id IS NULL');
            $this->connection->executeStatement('ALTER TABLE chat_message MODIFY receiver_id INT NOT NULL');
            $this->connection->executeStatement('ALTER TABLE chat_message ADD CONSTRAINT FK_chat_message_receiver FOREIGN KEY (receiver_id) REFERENCES user (id)');
            $this->connection->executeStatement('CREATE INDEX IDX_chat_message_receiver ON chat_message (receiver_id)');

            $io->success('Column receiver_id added successfully. You can use the chatroom now.');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('Failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
