<?php
require_once 'vendor/autoload.php';

use App\Kernel;
use App\Entity\FeedbackResponse;
use App\Entity\Workout;
use App\Entity\User;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');

// Find a coach to assign orphan entities to
$coach = $em->getRepository(User::class)->createQueryBuilder('u')
    ->where('u.roles LIKE :role')
    ->setParameter('role', '%ROLE_COACH%')
    ->setMaxResults(1)
    ->getQuery()
    ->getOneOrNullResult();

if (!$coach) {
    echo "No coach found in database.\n";
    exit;
}

echo "Assigning orphans to coach: " . $coach->getEmail() . " (ID: " . $coach->getId() . ")\n";

// Update Workouts
$workouts = $em->getRepository(Workout::class)->findBy(['coach' => null]);
foreach ($workouts as $w) {
    $w->setCoach($coach);
    echo "Assigned coach to Workout: " . $w->getNom() . "\n";
}

// Update Feedbacks
$feedbacks = $em->getRepository(FeedbackResponse::class)->findBy(['coach' => null]);
foreach ($feedbacks as $f) {
    $f->setCoach($coach);
    echo "Assigned coach to Feedback ID: " . $f->getId() . "\n";
}

$em->flush();
echo "Done.\n";
$kernel->shutdown();
