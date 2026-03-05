<?php
require_once 'vendor/autoload.php';

use App\Kernel;
use App\Entity\Workout;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');
$repo = $em->getRepository(Workout::class);

$idCandidate = '019cbc3d-1128-761a-aa6a-106df63602d4'; // From debug_all_workouts.php

echo "Testing findWithExercises for ID: $idCandidate\n";
$workout = $repo->findWithExercises($idCandidate);

if ($workout) {
    echo "SUCCESS: Found workout: " . $workout->getNom() . "\n";
} else {
    echo "FAILURE: Workout not found with standard findWithExercises.\n";
    
    echo "Testing direct QueryBuilder with type hint...\n";
    $qb = $repo->createQueryBuilder('w')
        ->leftJoin('w.exercises', 'e')->addSelect('e')
        ->where('w.id = :id')
        ->setParameter('id', $idCandidate, 'uuid');
    
    try {
        $workout3 = $qb->getQuery()->getOneOrNullResult();
        if ($workout3) {
            echo "SUCCESS: QueryBuilder worked WITH 'uuid' type hint!\n";
        } else {
            echo "FAILURE: QueryBuilder still failed even WITH 'uuid' type hint.\n";
        }
    } catch (\Exception $e) {
        echo "ERROR in QB: " . $e->getMessage() . "\n";
    }
}

$kernel->shutdown();
