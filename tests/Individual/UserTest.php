<?php
// tests/Individual/UserTest.php
error_reporting(0);
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Entity\User;
use App\Service\UserManager;

$manager = new UserManager();
$user = new User();

echo "\n--- Testing UserManager ---\n";

// Test Valid
$user->setEmail('test@sport.com');
try {
    $manager->validate($user, 'password123');
    echo "[PASS] Valid user accepted.\n";
} catch (\Exception $e) {
    echo "[FAIL] Valid user rejected: " . $e->getMessage() . "\n";
}

// Test Rule
try {
    $user->setEmail('invalid');
    $manager->validate($user, 'password123');
    echo "[FAIL] Invalid email was accepted!\n";
} catch (\InvalidArgumentException $e) {
    echo "[PASS] Invalid email rejected: " . $e->getMessage() . "\n";
}
echo "------------------------\n";
