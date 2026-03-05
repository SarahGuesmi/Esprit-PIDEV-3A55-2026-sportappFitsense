<?php

require __DIR__.'/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

$em = $container->get('doctrine')->getManager();

echo "=== DEBUG USER PROFILES ===\n\n";

$userRepo = $em->getRepository('App\Entity\User');
$users = $userRepo->findAll();

foreach ($users as $user) {
    echo "User: " . $user->getEmail() . "\n";
    echo "Role: " . (in_array('ROLE_COACH', $user->getRoles()) ? 'COACH' : 'USER') . "\n";
    
    $profiles = $user->getProfilesPhysiques();
    echo "Profiles count: " . count($profiles) . "\n";
    
    if (count($profiles) > 0) {
        foreach ($profiles as $profile) {
            echo "  Profile ID: " . $profile->getId() . "\n";
            $objectifs = $profile->getObjectifs();
            echo "  Objectifs count: " . count($objectifs) . "\n";
            if (count($objectifs) > 0) {
                foreach ($objectifs as $obj) {
                    echo "    - " . $obj->getName() . "\n";
                }
            }
        }
    } else {
        echo "  ⚠️ NO PROFILE!\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}
