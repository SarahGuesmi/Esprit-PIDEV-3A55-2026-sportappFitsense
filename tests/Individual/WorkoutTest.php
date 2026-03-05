<?php
// tests/Individual/WorkoutTest.php
error_reporting(0);
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Entity\Workout;
use App\Service\WorkoutManager;

$manager = new WorkoutManager();
$workout = new Workout();

echo "\n--- Testing WorkoutManager ---\n";

// Test Valid
$workout->setDuree(30)->setNiveau('Pro');
try {
    $manager->validate($workout);
    echo "[PASS] Valid workout accepted.\n";
} catch (\Exception $e) {
    echo "[FAIL] Valid workout rejected: " . $e->getMessage() . "\n";
}

// Test Rules
try {
    $workout->setDuree(0);
    $manager->validate($workout);
    echo "[FAIL] Zero duration was accepted!\n";
} catch (\InvalidArgumentException $e) {
    echo "[PASS] Zero duration rejected correctly: " . $e->getMessage() . "\n";
}
echo "----------------------------\n";
