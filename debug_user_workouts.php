<?php

require __DIR__.'/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

$em = $container->get('doctrine')->getManager();

echo "=== DEBUG USER WORKOUTS ===\n\n";

// Get a user
$userRepo = $em->getRepository('App\Entity\User');
$users = $userRepo->findAll();

if (empty($users)) {
    echo "No users found!\n";
    exit;
}

$user = $users[0];
echo "Testing with user: " . $user->getEmail() . "\n";
echo "User objectives: " . implode(', ', $user->getObjectifNames()) . "\n\n";

// Get workouts for this user
$workoutRepo = $em->getRepository('App\Entity\Workout');
$workouts = $workoutRepo->findByUserObjectifsFiltered($user, null, null);

echo "Total workouts found: " . count($workouts) . "\n\n";

foreach ($workouts as $workout) {
    echo "Workout: " . $workout->getNom() . "\n";
    echo "ID: " . $workout->getId() . "\n";
    echo "Niveau: " . $workout->getNiveau() . "\n";
    echo "Durée: " . $workout->getDuree() . " mins\n";
    
    $exercises = $workout->getExercises();
    echo "Exercises count: " . count($exercises) . "\n";
    
    if (count($exercises) > 0) {
        echo "Exercises:\n";
        foreach ($exercises as $exercise) {
            echo "  - " . $exercise->getNom() . "\n";
        }
    } else {
        echo "  ⚠️ NO EXERCISES!\n";
    }
    
    $objectifs = $workout->getObjectifs();
    echo "Objectifs: ";
    foreach ($objectifs as $obj) {
        echo $obj->getName() . " ";
    }
    echo "\n";
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}
