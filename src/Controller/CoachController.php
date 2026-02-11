<?php
// src/Controller/CoachController.php

namespace App\Controller;

use App\Entity\Exercise;
use App\Entity\Workout;
use App\Entity\ObjectifSportif;
use App\Form\ExerciseType;
use App\Form\WorkoutType;
use App\Repository\ExerciseRepository;
use App\Repository\WorkoutRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/coach')]
class CoachController extends AbstractController
{
    // ==================== WORKOUT CATALOG ====================
    
   #[Route('/workouts', name: 'coach_workout_catalog')]
        public function workoutCatalog(
            Request $request,
            EntityManagerInterface $em,
            ExerciseRepository $exerciseRepository
        ): Response {

            // Objectifs sélectionnés depuis l'URL (?objectifs[]=1&objectifs[]=2)
            $selectedObjectifs = $request->query->all('objectifs');

            $qb = $em->createQueryBuilder()
                ->select('w', 'o')
                ->from(Workout::class, 'w')
                ->leftJoin('w.objectifs', 'o');

            if (!empty($selectedObjectifs)) {
                $qb->andWhere('o.id IN (:ids)')
                ->setParameter('ids', $selectedObjectifs);
            }

            $workouts = $qb->getQuery()->getResult();

            // Tous les objectifs pour la liste déroulante
            $objectifs = $em->getRepository(ObjectifSportif::class)->findAll();

            $exerciseCount = $exerciseRepository->count([]);

            return $this->render('coach/workout_catalog.html.twig', [
                'workouts' => $workouts,
                'exerciseCount' => $exerciseCount,
                'objectifs' => $objectifs,
                'selectedObjectifs' => $selectedObjectifs,
            ]);
        }

    // ==================== EXERCISE MANAGEMENT ====================
    
    #[Route('/exercises', name: 'coach_exercise_list')]
    public function exerciseList(ExerciseRepository $exerciseRepository): Response
    {
        // Récupère tous les exercices triés par nom
        $exercises = $exerciseRepository->findBy([], ['nom' => 'ASC']);
        
        return $this->render('coach/exercise_list.html.twig', [
            'exercises' => $exercises,
        ]);
    }

    #[Route('/exercise/create', name: 'coach_exercise_create')]
    public function exerciseCreate(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $exercise = new Exercise();
        $form = $this->createForm(ExerciseType::class, $exercise);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($exercise);
            $em->flush();
            
            $this->addFlash('success', 'Exercice créé avec succès !');
            
            return $this->redirectToRoute('coach_exercise_list');
        }
        
        return $this->render('coach/exercise_form.html.twig', [
            'form' => $form->createView(),
            'isEdit' => false,
        ]);
    }

    #[Route('/exercise/{id}/edit', name: 'coach_exercise_edit')]
    public function exerciseEdit(
        Exercise $exercise,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(ExerciseType::class, $exercise);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            
            $this->addFlash('success', 'Exercice modifié avec succès !');
            
            return $this->redirectToRoute('coach_exercise_list');
        }
        
        return $this->render('coach/exercise_form.html.twig', [
            'form' => $form->createView(),
            'exercise' => $exercise,
            'isEdit' => true,
        ]);
    }

    #[Route('/exercise/{id}/delete', name: 'coach_exercise_delete', methods: ['POST'])]
    public function exerciseDelete(
        Exercise $exercise,
        EntityManagerInterface $em
    ): Response {
        $em->remove($exercise);
        $em->flush();
        
        $this->addFlash('success', 'Exercice supprimé avec succès !');
        
        return $this->redirectToRoute('coach_exercise_list');
    }

    // ==================== WORKOUT MANAGEMENT ====================
    
    #[Route('/workout/create', name: 'coach_workout_create')]
    public function workoutCreate(
        Request $request,
        EntityManagerInterface $em,
        ExerciseRepository $exerciseRepository
    ): Response {
        // Vérifier qu'il y a au moins un exercice
        $exerciseCount = $exerciseRepository->count([]);

        if ($exerciseCount === 0) {
            $this->addFlash('warning', 'Vous devez créer au moins un exercice avant de créer un workout.');
            return $this->redirectToRoute('coach_exercise_create');
        }

        $workout = new Workout();
        $form = $this->createForm(WorkoutType::class, $workout);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($workout);
            $em->flush();

            $this->addFlash('success', 'Workout créé avec succès !');
            return $this->redirectToRoute('coach_workout_catalog');
        }

        return $this->render('coach/workout_form.html.twig', [
            'form' => $form->createView(),
            'isEdit' => false,
        ]);
    }

    #[Route('/workout/{id}/edit', name: 'coach_workout_edit')]
    public function workoutEdit(
        Workout $workout,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(WorkoutType::class, $workout);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            
            $this->addFlash('success', 'Workout modifié avec succès !');
            
            return $this->redirectToRoute('coach_workout_catalog');
        }
        
        return $this->render('coach/workout_form.html.twig', [
            'form' => $form->createView(),
            'workout' => $workout,
            'isEdit' => true,
        ]);
    }

    #[Route('/workout/{id}/delete', name: 'coach_workout_delete', methods: ['POST'])]
    public function workoutDelete(
        Workout $workout,
        EntityManagerInterface $em
    ): Response {
        $em->remove($workout);
        $em->flush();
        
        $this->addFlash('success', 'Workout supprimé avec succès !');
        
        return $this->redirectToRoute('coach_workout_catalog');
    }

    #[Route('/workout/{id}/details', name: 'coach_workout_details')]
    public function workoutDetails(Workout $workout): Response
    {
        return $this->render('coach/workout_details.html.twig', [
            'workout' => $workout,
        ]);
    }
}