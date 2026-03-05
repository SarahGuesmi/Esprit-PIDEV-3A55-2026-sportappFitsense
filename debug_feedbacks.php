<?php
require_once 'vendor/autoload.php';

use App\Kernel;
use App\Entity\FeedbackResponse;
use App\Entity\Workout;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');

echo "--- Feedbacks ---\n";
$feedbacks = $em->getRepository(FeedbackResponse::class)->findAll();
foreach ($feedbacks as $f) {
    echo "ID: " . $f->getId() . " | Workout: " . ($f->getWorkout() ? $f->getWorkout()->getNom() : 'NULL') . " | Coach: " . ($f->getCoach() ? $f->getCoach()->getEmail() : 'NULL') . " | User: " . ($f->getUser() ? $f->getUser()->getEmail() : 'NULL') . " | Rating: " . $f->getRating() . "\n";
}

echo "\n--- Workouts with null Coach ---\n";
$workouts = $em->getRepository(Workout::class)->findBy(['coach' => null]);
foreach ($workouts as $w) {
    echo "ID: " . $w->getId() . " | Name: " . $w->getNom() . "\n";
}

$kernel->shutdown();
