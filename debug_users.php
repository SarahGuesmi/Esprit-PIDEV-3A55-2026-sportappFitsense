<?php
require_once 'vendor/autoload.php';

use App\Kernel;
use App\Entity\User;
use App\Entity\Workout;
use App\Entity\FeedbackResponse;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');

$users = $em->getRepository(User::class)->findAll();
echo "--- All Users ---\n";
foreach ($users as $u) {
    if (str_contains(strtolower($u->getFirstname() . ' ' . $u->getLastname()), 'coach')) {
        echo "ID: " . $u->getId() . " | Email: " . $u->getEmail() . " | Name: " . $u->getFirstname() . " " . $u->getLastname() . " | Roles: " . json_encode($u->getRoles()) . "\n";
    }
}

// Check workouts for NULL coaches
$nullWorkouts = $em->getRepository(Workout::class)->findBy(['coach' => null]);
echo "\n--- Workouts with NULL Coach: " . count($nullWorkouts) . " ---\n";
foreach ($nullWorkouts as $nw) {
    echo "ID: " . $nw->getId() . " | Name: " . $nw->getNom() . "\n";
}

// Check feedbacks for NULL coaches
$nullFeedbacks = $em->getRepository(FeedbackResponse::class)->findBy(['coach' => null]);
echo "\n--- Feedbacks with NULL Coach: " . count($nullFeedbacks) . " ---\n";
foreach ($nullFeedbacks as $nf) {
    echo "ID: " . $nf->getId() . " | Comment: " . $nf->getComment() . "\n";
}

$kernel->shutdown();
