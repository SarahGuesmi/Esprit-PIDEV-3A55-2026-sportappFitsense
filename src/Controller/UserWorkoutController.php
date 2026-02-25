<?php

namespace App\Controller;

use App\Repository\ExerciseRepository;
use App\Repository\WorkoutRepository;
use App\Repository\ObjectifSportifRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\UserExerciseProgressRepository;  // ← AJOUTER CETTE LIGNE
use App\Entity\UserExerciseProgress;                 // ← AJOUTER CETTE LIGNE
use Doctrine\ORM\EntityManagerInterface;             // ← AJOUTER CETTE LIGNE
use Symfony\Component\HttpFoundation\JsonResponse;   // ← AJOUTER CETTE LIGNE


#[Route('/user')]
#[IsGranted('ROLE_USER')]
class UserWorkoutController extends AbstractController
{
    #[Route('/my-workouts', name: 'user_my_workouts')]
    public function myWorkouts(
        Request $request,
        WorkoutRepository $workoutRepository
    ): Response {
        $user = $this->getUser();

        $niveau   = $request->query->get('niveau');
        $dureeMax = $request->query->get('duree');
        $dureeMax = ($dureeMax !== null && $dureeMax !== '') ? (int) $dureeMax : null;

        $workouts = $workoutRepository->findByUserObjectifsFiltered(
            $user,
            $niveau,
            $dureeMax
        );

        return $this->render('user/my_workouts.html.twig', [
            'workouts'       => $workouts,
            'userObjectifs'  => $user->getObjectifs(),
            'selectedNiveau' => $niveau,
            'selectedDuree'  => $dureeMax,
        ]);
    }

#[Route('/workout/{id}', name: 'user_workout_view')]
public function viewWorkout(
    int $id,
    WorkoutRepository $workoutRepository,
    UserExerciseProgressRepository $progressRepo
): Response {
    $user    = $this->getUser();
    $workout = $workoutRepository->find($id);

    if (!$workout) {
        throw $this->createNotFoundException('Workout non trouvé');
    }

    $hasAccess = $user->hasMatchingObjectif($workout);
    if (!$hasAccess) {
        $this->addFlash('warning', 'Ce workout ne correspond pas à vos objectifs.');
        return $this->redirectToRoute('user_my_workouts');
    }

    $doneExerciseIds = [];
    $totalExercises  = count($workout->getExercises());

    foreach ($workout->getExercises() as $exercise) {
        $progress = $progressRepo->findOneByUserAndExercise($user, $exercise);
        if ($progress && $progress->getStatus() === 'done') {
            $doneExerciseIds[] = $exercise->getId();
        }
    }

    $allDone = $totalExercises > 0 && count($doneExerciseIds) === $totalExercises;

    return $this->render('user/workout_view.html.twig', [
        'workout'         => $workout,
        'doneExerciseIds' => $doneExerciseIds,
        'allDone'         => $allDone,
        'doneCount'       => count($doneExerciseIds),
        'totalExercises'  => $totalExercises,
    ]);
}

            #[Route('/exercise/{id}', name: 'user_exercise_view')]
            public function viewExercise(
                int $id,
                ExerciseRepository $exerciseRepo,
                UserExerciseProgressRepository $progressRepo
            ): Response {
                $exercise = $exerciseRepo->find($id);
                if (!$exercise) {
                    throw $this->createNotFoundException('Exercise non trouvé');
                }

                $user     = $this->getUser();
                $progress = $progressRepo->findOneByUserAndExercise($user, $exercise);

                return $this->render('user/exercise_view.html.twig', [
                    'exercise' => $exercise,
                    'progress' => $progress,  // null si jamais commencé
                ]);
            }



   #[Route('/exercise/{id}/start', name: 'user_exercise_start', methods: ['POST'])]
public function startExercise(
    int $id,
    ExerciseRepository $exerciseRepo,
    UserExerciseProgressRepository $progressRepo,
    EntityManagerInterface $em
): JsonResponse {
    $exercise = $exerciseRepo->find($id);
    if (!$exercise) {
        return $this->json(['error' => 'Exercise non trouvé'], 404);
    }

    $user     = $this->getUser();
    $progress = $progressRepo->findOneByUserAndExercise($user, $exercise);

    if (!$progress) {
        $progress = new UserExerciseProgress();
        $progress->setUser($user);
        $progress->setExercise($exercise);
        $em->persist($progress);
    }

    if ($progress->getStatus() !== 'done') {
        $progress->setStatus('in_progress');
        $em->flush();
    }

    return $this->json([
        'status' => 'started',
        'duree'  => $exercise->getDuree(),
    ]);
}


#[Route('/exercise/{id}/done', name: 'user_exercise_done', methods: ['POST'])]
public function markExerciseDone(
    int $id,
    Request $request,
    ExerciseRepository $exerciseRepo,
    UserExerciseProgressRepository $progressRepo,
    EntityManagerInterface $em
): JsonResponse {
    $exercise = $exerciseRepo->find($id);
    if (!$exercise) {
        return $this->json(['error' => 'Exercise non trouvé'], 404);
    }

    $user     = $this->getUser();
    $progress = $progressRepo->findOneByUserAndExercise($user, $exercise);

    if (!$progress) {
        $progress = new UserExerciseProgress();
        $progress->setUser($user);
        $progress->setExercise($exercise);
        $em->persist($progress);
    }

    $data    = json_decode($request->getContent(), true);
    $elapsed = (int) ($data['elapsed'] ?? 0);
    $duree   = $exercise->getDuree();
    $depasse = $elapsed > $duree;

    $progress->setStatus('done');
    $progress->setElapsedTime($elapsed);
    $progress->setCompletedAt(new \DateTimeImmutable());
    $em->flush();

    return $this->json([
        'status'  => 'done',
        'elapsed' => $elapsed,
        'duree'   => $duree,
        'depasse' => $depasse,
    ]);
}





    #[Route('/workouts/filter/{objectifId}', name: 'user_workouts_by_objectif')]
    public function workoutsByObjectif(
        int $objectifId,
        ObjectifSportifRepository $objectifRepository,
        WorkoutRepository $workoutRepository
    ): Response {
        $objectif = $objectifRepository->find($objectifId);

        if (!$objectif) {
            throw $this->createNotFoundException('Objectif non trouvé');
        }

        $workouts = $workoutRepository->findByObjectif($objectif);

        return $this->render('user/workouts_by_objectif.html.twig', [
            'workouts' => $workouts,
            'objectif' => $objectif,
        ]);
    }

    #[Route('/workout/{id}/done', name: 'user_workout_done', methods: ['POST'])]
public function markWorkoutDone(
    int $id,
    WorkoutRepository $workoutRepository,
    UserExerciseProgressRepository $progressRepo,
    EntityManagerInterface $em
): JsonResponse {
    $user    = $this->getUser();
    $workout = $workoutRepository->find($id);

    if (!$workout) {
        return $this->json(['error' => 'Workout non trouvé'], 404);
    }

    // Vérifier que TOUS les exercices sont done
    $exercises   = $workout->getExercises();
    $totalCount  = count($exercises);

    if ($totalCount === 0) {
        return $this->json(['error' => 'Aucun exercice'], 400);
    }

    $doneCount = 0;
    foreach ($exercises as $exercise) {
        $progress = $progressRepo->findOneByUserAndExercise($user, $exercise);
        if ($progress && $progress->getStatus() === 'done') {
            $doneCount++;
        }
    }

    if ($doneCount < $totalCount) {
        return $this->json([
            'error'   => 'Pas tous les exercices sont complétés',
            'done'    => $doneCount,
            'total'   => $totalCount,
        ], 400);
    }

    // Tous done → marquer le workout done
    $workout->setStatus('done');
    $em->flush();

    return $this->json([
        'status' => 'done',
        'message' => 'Workout complété !',
    ]);
}
}