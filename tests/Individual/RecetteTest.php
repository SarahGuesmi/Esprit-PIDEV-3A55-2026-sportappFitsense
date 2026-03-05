<?php
// tests/Individual/RecetteTest.php
error_reporting(0);
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Entity\RecetteNutritionnelle;
use App\Service\RecetteManager;

$manager = new RecetteManager();
$recette = new RecetteNutritionnelle();

echo "\n--- Testing RecetteManager ---\n";

// Test Valid
$recette->setTitle('Salade')->setKcal(200);
try {
    $manager->validate($recette);
    echo "[PASS] Valid recipe accepted.\n";
} catch (\Exception $e) {
    echo "[FAIL] Valid recipe rejected: " . $e->getMessage() . "\n";
}

// Test Rule
try {
    $recette->setTitle('');
    $manager->validate($recette);
    echo "[FAIL] Empty title was accepted!\n";
} catch (\InvalidArgumentException $e) {
    echo "[PASS] Empty title rejected: " . $e->getMessage() . "\n";
}
echo "---------------------------\n";
