<?php
require_once 'vendor/autoload.php';

use App\Kernel;
use App\Entity\Workout;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');

$workouts = $em->getRepository(Workout::class)->findAll();
echo "--- All Workouts ---\n";
foreach ($workouts as $w) {
    $coachId = $w->getCoach() ? $w->getCoach()->getId() : 'NULL';
    echo "ID: " . $w->getId() . " | Name: " . $w->getNom() . " | CoachID: " . $coachId . "\n";
}

$kernel->shutdown();
