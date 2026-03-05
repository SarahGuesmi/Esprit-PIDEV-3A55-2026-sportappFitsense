<?php

use App\Entity\User;
use App\Repository\RecetteNutritionnelleRepository;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$kernel = new \App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

$em = $container->get('doctrine')->getManager();
$recetteRepo = $em->getRepository(\App\Entity\RecetteNutritionnelle::class);
$userRepo = $em->getRepository(\App\Entity\User::class);

$coachEmail = 'mayssabenyoussef47@gmail.com';
$coach = $userRepo->findOneBy(['email.email' => $coachEmail]);

echo "COACH: $coachEmail (ID: " . ($coach ? $coach->getId() : 'NOT FOUND') . ")\n";

echo "\n1. Testing raw DQL (No Filter):\n";
$dql1 = "SELECT r.title, COUNT(fav) as favorites FROM App\Entity\RecetteNutritionnelle r LEFT JOIN r.favoritedBy fav GROUP BY r.id";
$res1 = $em->createQuery($dql1)->getScalarResult();
print_r($res1);

echo "\n2. Testing raw DQL (With Coach Filter):\n";
$dql2 = "SELECT r.title, COUNT(fav) as favorites FROM App\Entity\RecetteNutritionnelle r LEFT JOIN r.favoritedBy fav WHERE r.coach = :coach GROUP BY r.id";
$res2 = $em->createQuery($dql2)->setParameter('coach', $coach)->getScalarResult();
print_r($res2);

echo "\n3. Checking recipes mapping via findBy:\n";
$recipes = $recetteRepo->findBy(['coach' => $coach]);
foreach ($recipes as $r) {
    echo "- Recipe: " . $r->getTitle() . " (Coach ID in Entity: " . ($r->getCoach() ? $r->getCoach()->getId() : 'NULL') . ")\n";
}

echo "\nDONE\n";
