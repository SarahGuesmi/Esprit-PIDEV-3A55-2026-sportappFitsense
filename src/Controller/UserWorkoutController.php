<?php
// src/Controller/UserWorkoutController.php

namespace App\Controller;

use App\Repository\WorkoutRepository;
use App\Repository\ObjectifRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
#[IsGranted('ROLE_USER')]
class UserWorkoutController extends AbstractController
{
    #[Route('/my-workouts', name: 'user_my_workouts')]
    public function myWorkouts(
        WorkoutRepository $workoutRepository
    ): Response {
        $user = $this->getUser();
        
        // Récupérer les workouts qui correspondent aux objectifs de l'utilisateur
        $matchingWorkouts = $workoutRepository->findByUserObjectifs($user);
        
        return $this->render('user/my_workouts.html.twig', [
            'workouts' => $matchingWorkouts,
            'userObjectifs' => $user->getObjectifs(),
        ]);
    }

    #[Route('/workout/{id}', name: 'user_workout_view')]
    public function viewWorkout(
        int $id,
        WorkoutRepository $workoutRepository
    ): Response {
        $user = $this->getUser();
        $workout = $workoutRepository->find($id);
        
        if (!$workout) {
            throw $this->createNotFoundException('Workout non trouvé');
        }
        
        // Vérifier si l'utilisateur a au moins un objectif correspondant
        $hasAccess = $user->hasMatchingObjectif($workout);
        
        if (!$hasAccess) {
            $this->addFlash('warning', 'Ce workout ne correspond pas à vos objectifs.');
            return $this->redirectToRoute('user_my_workouts');
        }
        
        return $this->render('user/workout_view.html.twig', [
            'workout' => $workout,
        ]);
    }

    #[Route('/workouts/filter/{objectifId}', name: 'user_workouts_by_objectif')]
    public function workoutsByObjectif(
        int $objectifId,
        ObjectifRepository $objectifRepository,
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
}