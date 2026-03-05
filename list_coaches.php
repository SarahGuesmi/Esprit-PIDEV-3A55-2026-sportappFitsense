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

$coaches = $em->getRepository(User::class)->findAll();
echo "--- All Coaches ---\n";
foreach ($coaches as $c) {
    if (in_array('ROLE_COACH', $c->getRoles())) {
        echo "ID: " . $c->getId() . " | Email: " . $c->getEmail() . " | Name: " . $c->getFirstname() . " " . $c->getLastname() . "\n";
    }
}

$kernel->shutdown();
