<?php

namespace App\Command;

use App\Service\WeeklyReportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-daily-report',
    description: 'Send daily feedback report to all coaches',
)]
class SendWeeklyReportCommand extends Command
{
    public function __construct(
        private WeeklyReportService $reportService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('coach-email', 'c', InputOption::VALUE_REQUIRED, 'Send report to a specific coach email')
            ->addOption('test', 't', InputOption::VALUE_NONE, 'Use today\'s data for testing')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $coachEmail = $input->getOption('coach-email');
        $useToday = $input->getOption('test');

        $io->title('📧 Sending Daily Feedback Reports');

        if ($useToday) {
            $io->warning('⚠️  TEST MODE: Using today\'s data');
        }

        if ($coachEmail) {
            $io->info("Sending report to: $coachEmail");
            // TODO: Implement single coach sending
            $io->warning('Single coach sending not yet implemented. Sending to all coaches...');
        }

        $results = $this->reportService->sendToAllCoaches($useToday);

        $io->section('Results');
        $io->success(sprintf('✅ Sent: %d', $results['sent']));
        $io->info(sprintf('⏭️  Skipped (no feedbacks): %d', $results['skipped']));
        
        if ($results['failed'] > 0) {
            $io->error(sprintf('❌ Failed: %d', $results['failed']));
        }

        if (!empty($results['details'])) {
            $io->section('Details');
            $tableData = [];
            foreach ($results['details'] as $detail) {
                $status = match($detail['status']) {
                    'sent' => '✅ Sent',
                    'failed' => '❌ Failed',
                    default => '⏭️  Skipped'
                };
                $tableData[] = [
                    $detail['coach'],
                    $status,
                    $detail['message']
                ];
            }
            $io->table(['Coach', 'Status', 'Message'], $tableData);
        }

        return Command::SUCCESS;
    }
}
