<?php

require __DIR__.'/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

$em = $container->get('doctrine')->getManager();

echo "=== DEBUG OBJECTIFS ===\n\n";

// Check all users and their objectives
$userRepo = $em->getRepository('App\Entity\User');
$users = $userRepo->findAll();

echo "=== USERS AND THEIR OBJECTIVES ===\n\n";
foreach ($users as $user) {
    echo "User: " . $user->getEmail() . "\n";
    echo "Role: " . (in_array('ROLE_COACH', $user->getRoles()) ? 'COACH' : 'USER') . "\n";
    
    $objectifs = $user->getObjectifs();
    echo "Objectifs count: " . count($objectifs) . "\n";
    
    if (count($objectifs) > 0) {
        echo "Objectifs:\n";
        foreach ($objectifs as $obj) {
            echo "  - " . $obj->getName() . " (ID: " . $obj->getId() . ")\n";
        }
    } else {
        echo "  ⚠️ NO OBJECTIVES!\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}

// Check all workouts and their objectives
$workoutRepo = $em->getRepository('App\Entity\Workout');
$workouts = $workoutRepo->findAll();

echo "\n=== WORKOUTS AND THEIR OBJECTIVES ===\n\n";
foreach ($workouts as $workout) {
    echo "Workout: " . $workout->getNom() . "\n";
    echo "ID: " . $workout->getId() . "\n";
    
    $objectifs = $workout->getObjectifs();
    echo "Objectifs count: " . count($objectifs) . "\n";
    
    if (count($objectifs) > 0) {
        echo "Objectifs:\n";
        foreach ($objectifs as $obj) {
            echo "  - " . $obj->getName() . " (ID: " . $obj->getId() . ")\n";
        }
    } else {
        echo "  ⚠️ NO OBJECTIVES!\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}

// Check all ObjectifSportif entries
$objectifRepo = $em->getRepository('App\Entity\ObjectifSportif');
$allObjectifs = $objectifRepo->findAll();

echo "\n=== ALL OBJECTIF SPORTIF ENTRIES ===\n\n";
echo "Total: " . count($allObjectifs) . "\n\n";
foreach ($allObjectifs as $obj) {
    echo "ID: " . $obj->getId() . "\n";
    echo "Name: " . $obj->getName() . "\n";
    echo "Profile: " . ($obj->getProfile() ? $obj->getProfile()->getId() : 'NULL') . "\n";
    echo "\n";
}
