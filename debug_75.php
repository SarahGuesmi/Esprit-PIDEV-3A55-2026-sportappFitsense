<?php
require_once 'vendor/autoload.php';

use App\Kernel;
use App\Entity\Workout;
use App\Entity\FeedbackResponse;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');

$coachId = 75;

$workouts75 = $em->getRepository(Workout::class)->findBy(['coach' => $coachId]);
echo "--- Workouts for Coach 75 (WA Coach): " . count($workouts75) . " ---\n";
foreach ($workouts75 as $w) {
    echo "ID: " . $w->getId() . " | Name: " . $w->getNom() . "\n";
}

$feedbacks75 = $em->getRepository(FeedbackResponse::class)->findBy(['coach' => $coachId]);
echo "\n--- Feedbacks for Coach 75 (WA Coach): " . count($feedbacks75) . " ---\n";
foreach ($feedbacks75 as $f) {
    echo "ID: " . $f->getId() . " | Comment: " . $f->getComment() . "\n";
}

$nullWorkouts = $em->getRepository(Workout::class)->findBy(['coach' => null]);
echo "\n--- Workouts with NULL Coach: " . count($nullWorkouts) . " ---\n";

$nullFeedbacks = $em->getRepository(FeedbackResponse::class)->findBy(['coach' => null]);
echo "\n--- Feedbacks with NULL Coach: " . count($nullFeedbacks) . " ---\n";

$kernel->shutdown();
