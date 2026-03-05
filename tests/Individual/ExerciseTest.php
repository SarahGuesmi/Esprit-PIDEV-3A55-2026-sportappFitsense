<?php
// tests/Individual/ExerciseTest.php
error_reporting(0);
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Entity\Exercise;
use App\Service\ExerciseManager;

$manager = new ExerciseManager();
$exercise = new Exercise();

echo "\n--- Testing ExerciseManager ---\n";

// Test Valid
$exercise->setNom('Pushup')->setType('Strength');
try {
    $manager->validate($exercise);
    echo "[PASS] Valid exercise accepted.\n";
} catch (\Exception $e) {
    echo "[FAIL] Valid exercise rejected: " . $e->getMessage() . "\n";
}

// Test Rule
try {
    $exercise->setNom('');
    $manager->validate($exercise);
    echo "[FAIL] Empty name was accepted!\n";
} catch (\InvalidArgumentException $e) {
    echo "[PASS] Empty name rejected: " . $e->getMessage() . "\n";
}
echo "---------------------------\n";
