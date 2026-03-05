<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entity\Workout;
use App\Entity\RecetteNutritionnelle;
use App\Entity\Exercise;
use App\Entity\User;
use App\Entity\ProfilePhysique;
use App\Entity\EtatMental;
use App\Service\WorkoutManager;
use App\Service\RecetteManager;
use App\Service\ExerciseManager;
use App\Service\UserManager;
use App\Service\ProfilePhysiqueManager;
use App\Service\EtatMentalManager;

echo "Starting manual verification of 6 Business Managers...\n";

function test(string $name, callable $fn) {
    echo "Testing $name: ";
    try {
        $fn();
        echo "OK\n";
    } catch (\Throwable $e) {
        echo "FAILED - " . $e->getMessage() . "\n";
    }
}

// 1. Workout
test('WorkoutManager', function() {
    $manager = new WorkoutManager();
    $w = new Workout();
    $w->setDuree(30)->setNiveau('Pro');
    if (!$manager->validate($w)) throw new Exception("Valid workout failed");
    
    try {
        $w->setDuree(-1);
        $manager->validate($w);
        throw new Exception("Negative duration should fail");
    } catch (\InvalidArgumentException $e) {}
});

// 2. Recette
test('RecetteManager', function() {
    $manager = new RecetteManager();
    $r = new RecetteNutritionnelle();
    $r->setTitle('Pasta')->setKcal(500);
    if (!$manager->validate($r)) throw new Exception("Valid recipe failed");
    
    try {
        $r->setKcal(0);
        $manager->validate($r);
        throw new Exception("Zero kcal should fail");
    } catch (\InvalidArgumentException $e) {}
});

// 3. Exercise
test('ExerciseManager', function() {
    $manager = new ExerciseManager();
    $e = new Exercise();
    $e->setNom('Pushup')->setType('Strength');
    if (!$manager->validate($e)) throw new Exception("Valid exercise failed");
    
    try {
        $e->setNom('');
        $manager->validate($e);
        throw new Exception("Empty name should fail");
    } catch (\InvalidArgumentException $e) {}
});

// 4. User
test('UserManager', function() {
    $manager = new UserManager();
    $u = new User();
    $u->setEmail('valid@mail.com');
    if (!$manager->validate($u, 'password123')) throw new Exception("Valid user failed");
    
    try {
        $manager->validate($u, 'short');
        throw new Exception("Short password should fail");
    } catch (\InvalidArgumentException $e) {}
});

// 5. ProfilePhysique
test('ProfilePhysiqueManager', function() {
    $manager = new ProfilePhysiqueManager();
    $p = new ProfilePhysique();
    $p->setWeight(70)->setHeight(170);
    if (!$manager->validate($p)) throw new Exception("Valid profile failed");
    
    try {
        $p->setWeight(10);
        $manager->validate($p);
        throw new Exception("Low weight should fail");
    } catch (\InvalidArgumentException $e) {}
});

// 6. EtatMental
test('EtatMentalManager', function() {
    $manager = new EtatMentalManager();
    $m = new EtatMental();
    $m->setStressLevel(3)->setMood(4);
    if (!$manager->validate($m)) throw new Exception("Valid mental state failed");
    
    try {
        $m->setStressLevel(6);
        $manager->validate($m);
        throw new Exception("High stress should fail");
    } catch (\InvalidArgumentException $e) {}
});

echo "\nVerification complete!\n";
