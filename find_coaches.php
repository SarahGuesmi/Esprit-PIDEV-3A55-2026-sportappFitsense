<?php
require_once 'vendor/autoload.php';

use App\Kernel;
use App\Entity\User;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');

$users = $em->getRepository(User::class)->findAll();
echo "--- Coaches ---\n";
foreach ($users as $u) {
    if (in_array('ROLE_COACH', $u->getRoles())) {
        echo "ID: " . $u->getId() . " | Email: " . $u->getEmail() . "\n";
    }
}

$kernel->shutdown();
