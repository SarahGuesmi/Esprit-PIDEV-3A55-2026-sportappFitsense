<?php
// tests/Individual/ProfilePhysiqueTest.php
error_reporting(0);
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Entity\ProfilePhysique;
use App\Service\ProfilePhysiqueManager;

$manager = new ProfilePhysiqueManager();
$profile = new ProfilePhysique();

echo "\n--- Testing ProfilePhysiqueManager ---\n";

// Test Valid
$profile->setWeight(70)->setHeight(170);
try {
    $manager->validate($profile);
    echo "[PASS] Valid profile accepted.\n";
} catch (\Exception $e) {
    echo "[FAIL] Valid profile rejected: " . $e->getMessage() . "\n";
}

// Test Rule
try {
    $profile->setWeight(400);
    $manager->validate($profile);
    echo "[FAIL] Heavy weight was accepted!\n";
} catch (\InvalidArgumentException $e) {
    echo "[PASS] Invalid weight rejected: " . $e->getMessage() . "\n";
}
echo "---------------------------\n";
