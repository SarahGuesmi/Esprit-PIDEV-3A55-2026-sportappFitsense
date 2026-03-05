<?php
require_once 'vendor/autoload.php';

use App\Kernel;
use App\Entity\FeedbackResponse;
use App\Entity\User;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');

$feedbacks = $em->getRepository(FeedbackResponse::class)->findAll();
echo "--- All Feedbacks ---\n";
foreach ($feedbacks as $f) {
    $coachId = $f->getCoach() ? $f->getCoach()->getId() : 'NULL';
    $workoutId = $f->getWorkout() ? $f->getWorkout()->getId() : 'NULL';
    echo "ID: " . $f->getId() . " | CoachID: " . $coachId . " | WorkoutID: " . $workoutId . " | Sentiment: " . $f->getSentiment() . " | Comment: " . $f->getComment() . "\n";
}

$coaches = $em->getRepository(User::class)->createQueryBuilder('u')
    ->where('u.roles LIKE :role')
    ->setParameter('role', '%ROLE_COACH%')
    ->getQuery()
    ->getResult();

echo "\n--- All Coaches ---\n";
foreach ($coaches as $c) {
    echo "ID: " . $c->getId() . " | Email: " . $c->getEmail() . " | Name: " . $c->getFirstname() . " " . $c->getLastname() . "\n";
}

$kernel->shutdown();
