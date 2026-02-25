<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:chat-add-read-at-column',
    description: 'Add read_at column to chat_message table if missing (for unread message tracking).',
)]
class ChatAddReadAtColumnCommand extends Command
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
            $hasReadAt = false;
            foreach ($columns as $col) {
                if (strtolower($col->getName()) === 'read_at') {
                    $hasReadAt = true;
                    break;
                }
            }

            if ($hasReadAt) {
                $io->success('Column read_at already exists on chat_message. Nothing to do.');
                return Command::SUCCESS;
            }

            $io->writeln('Adding read_at to chat_message...');

            $this->connection->executeStatement(
                "ALTER TABLE chat_message ADD read_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'"
            );

            $io->success('Column read_at added successfully. Unread badges will work now.');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('Failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
