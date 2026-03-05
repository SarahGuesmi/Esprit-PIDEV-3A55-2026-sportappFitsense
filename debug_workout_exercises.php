<?php

require __DIR__.'/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

$em = $container->get('doctrine')->getManager();

echo "=== DEBUG WORKOUT EXERCISES ===\n\n";

// Get all workouts
$workouts = $em->getRepository('App\Entity\Workout')->findAll();

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
            echo "  - " . $exercise->getNom() . " (ID: " . $exercise->getId() . ")\n";
        }
    } else {
        echo "  ⚠️ NO EXERCISES FOUND!\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}

// Check the workout_exercise join table
echo "\n=== CHECKING workout_exercise JOIN TABLE ===\n\n";
$conn = $em->getConnection();
$sql = "SELECT * FROM workout_exercise LIMIT 10";
$stmt = $conn->prepare($sql);
$result = $stmt->executeQuery();
$rows = $result->fetchAllAssociative();

echo "Total rows in workout_exercise: " . count($rows) . "\n";
foreach ($rows as $row) {
    print_r($row);
}
