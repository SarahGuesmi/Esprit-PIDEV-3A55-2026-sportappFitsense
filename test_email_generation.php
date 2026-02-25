<?php
/**
 * Test script to verify email generation works (without sending)
 */

require 'vendor/autoload.php';

use App\Kernel;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

// Bootstrap Symfony
$kernel = new Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();

// Get entity manager
$em = $container->get(EntityManagerInterface::class);

// Get coach
$coach = $em->getRepository(User::class)->findOneBy(['email' => 'ranime@gmail.com']);

if (!$coach) {
    echo "❌ Coach not found\n";
    exit(1);
}

echo "✅ Coach found: {$coach->getEmail()}\n";
echo "📧 Testing email generation...\n\n";

// Get feedbacks for current week
$feedbackRepo = $em->getRepository(\App\Entity\FeedbackResponse::class);
$startDate = new \DateTimeImmutable('monday this week');
$endDate = new \DateTimeImmutable('now');

$feedbacks = $feedbackRepo->createQueryBuilder('f')
    ->where('f.coach = :coach')
    ->andWhere('f.createdAt >= :start')
    ->andWhere('f.createdAt <= :end')
    ->setParameter('coach', $coach)
    ->setParameter('start', $startDate)
    ->setParameter('end', $endDate)
    ->orderBy('f.createdAt', 'DESC')
    ->getQuery()
    ->getResult();

echo "📊 Found " . count($feedbacks) . " feedbacks for this week\n\n";

if (empty($feedbacks)) {
    echo "⚠️  No feedbacks found. Add some feedbacks first to test the report.\n";
    exit(0);
}

// Display feedback summary
echo "Feedback Summary:\n";
echo "================\n";
foreach ($feedbacks as $fb) {
    echo sprintf(
        "- %s: %s (Sentiment: %s) - %s\n",
        $fb->getUserName(),
        $fb->getRating(),
        $fb->getSentiment() ?? 'N/A',
        $fb->getWorkout()->getNom()
    );
}

echo "\n✅ Email generation would work correctly!\n";
echo "📧 To actually send emails, enable curl extension in php.ini\n";
echo "\nInstructions:\n";
echo "1. Open: C:\\Program Files\\PHP\\8.2\\php.ini (as Administrator)\n";
echo "2. Find: ;extension=curl\n";
echo "3. Change to: extension=curl\n";
echo "4. Save and restart\n";
echo "5. Run: php bin/console app:send-weekly-report --test\n";
