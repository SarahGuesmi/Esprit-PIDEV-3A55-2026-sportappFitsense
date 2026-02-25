<?php

namespace App\Command;

use App\Entity\FeedbackResponse;
use App\Service\FeedbackAnalysisService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:analyze-feedback',
    description: 'Analyze existing feedback comments with OpenAI',
)]
class AnalyzeFeedbackCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private FeedbackAnalysisService $analysisService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Re-analyze all feedbacks, even those already analyzed')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Limit number of feedbacks to analyze', 0)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = $input->getOption('force');
        $limit = (int) $input->getOption('limit');

        $io->title('🤖 Analyzing Feedback Comments with OpenAI');

        // Get feedbacks to analyze
        $qb = $this->em->getRepository(FeedbackResponse::class)
            ->createQueryBuilder('f')
            ->where('f.comment IS NOT NULL')
            ->andWhere('f.comment != :empty')
            ->setParameter('empty', '');

        if (!$force) {
            $qb->andWhere('f.sentiment IS NULL');
        }

        if ($limit > 0) {
            $qb->setMaxResults($limit);
        }

        $feedbacks = $qb->getQuery()->getResult();

        if (empty($feedbacks)) {
            $io->success('No feedbacks to analyze!');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Found %d feedbacks to analyze', count($feedbacks)));
        $io->progressStart(count($feedbacks));

        $analyzed = 0;
        $errors = 0;

        foreach ($feedbacks as $feedback) {
            try {
                $analysis = $this->analysisService->analyzeFeedback($feedback->getComment());
                
                $feedback->setSentiment($analysis['sentiment']);
                $feedback->setKeywords($analysis['keywords']);
                $feedback->setAiSummary($analysis['summary']);
                
                $this->em->flush();
                
                $analyzed++;
                $io->progressAdvance();
                
                // Small delay to avoid rate limiting
                usleep(500000); // 0.5 second
                
            } catch (\Exception $e) {
                $errors++;
                $io->error(sprintf('Error analyzing feedback #%d: %s', $feedback->getId(), $e->getMessage()));
            }
        }

        $io->progressFinish();

        $io->success(sprintf(
            'Analysis complete! ✅ %d analyzed, ❌ %d errors',
            $analyzed,
            $errors
        ));

        return Command::SUCCESS;
    }
}
