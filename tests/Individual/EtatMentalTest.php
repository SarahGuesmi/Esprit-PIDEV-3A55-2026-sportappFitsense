<?php
// tests/Individual/EtatMentalTest.php
error_reporting(0);
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Entity\EtatMental;
use App\Service\EtatMentalManager;

$manager = new EtatMentalManager();
$etat = new EtatMental();

echo "\n--- Testing EtatMentalManager ---\n";

// Test Valid
$etat->setStressLevel(3)->setMood(4);
try {
    $manager->validate($etat);
    echo "[PASS] Valid mental state accepted.\n";
} catch (\Exception $e) {
    echo "[FAIL] Valid mental state rejected: " . $e->getMessage() . "\n";
}

// Test Rule 1: Stress
try {
    $etat->setStressLevel(10);
    $manager->validate($etat);
    echo "[FAIL] Invalide stress level was accepted!\n";
} catch (\InvalidArgumentException $e) {
    echo "[PASS] Invalid stress rejected correctly: " . $e->getMessage() . "\n";
}

// Test Rule 2: Mood
try {
    $etat->setStressLevel(3)->setMood(0);
    $manager->validate($etat);
    echo "[FAIL] Invalid mood level was accepted!\n";
} catch (\InvalidArgumentException $e) {
    echo "[PASS] Invalid mood rejected correctly: " . $e->getMessage() . "\n";
}
echo "-------------------------------\n";
