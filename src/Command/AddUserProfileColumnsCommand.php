<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Adds user profile columns (phone, photo) if they are missing.
 * Run this if you get "Unknown column 't0.phone'" after adding the profile feature.
 */
#[AsCommand(
    name: 'app:user-profile-columns',
    description: 'Add user profile columns (phone, photo) to the user table if missing',
)]
class AddUserProfileColumnsCommand extends Command
{
    public function __construct(
        private readonly Connection $connection
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $columns = [
            'phone' => 'VARCHAR(50) DEFAULT NULL',
            'photo' => 'VARCHAR(255) DEFAULT NULL',
        ];

        $existing = $this->connection->createSchemaManager()->listTableColumns('user');
        $existingNames = array_map(fn ($c) => strtolower($c->getName()), $existing);

        $added = 0;
        foreach ($columns as $name => $def) {
            if (in_array(strtolower($name), $existingNames, true)) {
                $io->comment("Column <info>user.{$name}</info> already exists.");
                continue;
            }
            $this->connection->executeStatement("ALTER TABLE user ADD {$name} {$def}");
            $io->text("Added column <info>user.{$name}</info>.");
            $added++;
        }

        if ($added > 0) {
            $io->success("Added {$added} column(s). You can log in now.");
        } else {
            $io->success('All profile columns already exist.');
        }

        return Command::SUCCESS;
    }
}
