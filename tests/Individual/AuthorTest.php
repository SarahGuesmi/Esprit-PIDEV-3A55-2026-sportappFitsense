<?php
// tests/Individual/AuthorTest.php
error_reporting(0);
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Entity\Author;
use App\Service\AuthorManager;

$manager = new AuthorManager();
$author = new Author();

echo "\n--- Testing AuthorManager ---\n";

// Test Valid
$author->setName('Victor Hugo')->setEmail('victor.hugo@gmail.com');
try {
    $manager->validate($author);
    echo "[PASS] Valid author accepted.\n";
} catch (\Exception $e) {
    echo "[FAIL] Valid author rejected: " . $e->getMessage() . "\n";
}

// Test Missing Name
$author->setName('')->setEmail('test@gmail.com');
try {
    $manager->validate($author);
    echo "[FAIL] Empty name was accepted!\n";
} catch (\InvalidArgumentException $e) {
    echo "[PASS] Missing name rejected: " . $e->getMessage() . "\n";
}

// Test Invalid Email
$author->setName('Author')->setEmail('invalid');
try {
    $manager->validate($author);
    echo "[FAIL] Invalid email was accepted!\n";
} catch (\InvalidArgumentException $e) {
    echo "[PASS] Invalid email rejected: " . $e->getMessage() . "\n";
}
echo "---------------------------\n";
