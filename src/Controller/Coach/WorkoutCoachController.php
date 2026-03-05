<?php

namespace App\Controller\Coach;

use App\Entity\Exercise;
use App\Entity\Workout;
use App\Entity\ObjectifSportif;
use App\Form\WorkoutType;
use App\Form\ExerciseType;
use App\Repository\WorkoutRepository;
use App\Repository\ExerciseRepository;
use App\Repository\ObjectifSportifRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/coach')]
#[IsGranted('ROLE_COACH')]
class WorkoutCoachController extends AbstractController
{
    // ═══════════════════════════════════════════
    //  WORKOUT CATALOG
    // ═══════════════════════════════════════════

    #[Route('/workouts', name: 'coach_workout_catalog')]
    public function catalog(
        Request $request,
        WorkoutRepository $workoutRepo,
        ExerciseRepository $exerciseRepo,
        ObjectifSportifRepository $objectifRepo
    ): Response {
        $q                = $request->query->get('q', '');
        $selectedObjectifs = $request->query->all('objectifs');
        $isAjax           = $request->isXmlHttpRequest() || $request->query->get('ajax');

        $qb = $workoutRepo->createQueryBuilder('w')
            ->orderBy('w.nom', 'ASC');

        if ($q !== '') {
            $qb->andWhere('LOWER(w.nom) LIKE :q OR LOWER(w.description) LIKE :q')
               ->setParameter('q', '%' . strtolower($q) . '%');
        }

        if (!empty($selectedObjectifs)) {
            $qb->innerJoin('w.objectifs', 'o')
               ->andWhere('o.id IN (:objectifs)')
               ->setParameter('objectifs', $selectedObjectifs);
        }

        $workouts      = $qb->getQuery()->getResult();
        $objectifs     = $objectifRepo->findAll();
        $exerciseCount = $exerciseRepo->count([]);

        if ($isAjax) {
            return $this->render('coach/workout_catalog/_list.html.twig', [
                'workouts' => $workouts,
            ]);
        }

        return $this->render('coach/workout_catalog.html.twig', [
            'workouts'          => $workouts,
            'objectifs'         => $objectifs,
            'selectedObjectifs' => $selectedObjectifs,
            'exerciseCount'     => $exerciseCount,
            'q'                 => $q,
        ]);
    }

    // ═══════════════════════════════════════════
    //  WORKOUT CREATE
    // ═══════════════════════════════════════════

    #[Route('/workouts/create', name: 'coach_workout_create')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $workout = new Workout();
        $workout->setCoach($this->getUser());

        $form = $this->createForm(WorkoutType::class, $workout);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($workout);
            $em->flush();

            $this->addFlash('success', 'Workout "' . $workout->getNom() . '" created successfully!');
            return $this->redirectToRoute('coach_workout_catalog');
        }

        return $this->render('coach/workout_form.html.twig', [
            'form'    => $form->createView(),
            'workout' => $workout,
            'isEdit'  => false,
        ]);
    }

    // ═══════════════════════════════════════════
    //  WORKOUT EDIT
    // ═══════════════════════════════════════════

    #[Route('/workouts/{id}/edit', name: 'coach_workout_edit')]
    public function edit(Workout $workout, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(WorkoutType::class, $workout);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Workout "' . $workout->getNom() . '" updated successfully!');
            return $this->redirectToRoute('coach_workout_catalog');
        }

        return $this->render('coach/workout_form.html.twig', [
            'form'    => $form->createView(),
            'workout' => $workout,
            'isEdit'  => true,
        ]);
    }

    // ═══════════════════════════════════════════
    //  WORKOUT DELETE
    // ═══════════════════════════════════════════

    #[Route('/workouts/{id}/delete', name: 'coach_workout_delete', methods: ['POST'])]
    public function delete(Workout $workout, EntityManagerInterface $em): Response
    {
        $em->remove($workout);
        $em->flush();

        $this->addFlash('success', 'Workout deleted successfully.');
        return $this->redirectToRoute('coach_workout_catalog');
    }

    // ═══════════════════════════════════════════
    //  WORKOUT DETAILS
    // ═══════════════════════════════════════════

    #[Route('/workouts/{id}', name: 'coach_workout_details')]
    public function details(Workout $workout): Response
    {
        return $this->render('coach/workout_details.html.twig', [
            'workout' => $workout,
        ]);
    }

    // ═══════════════════════════════════════════
    //  EXERCISE LIST
    // ═══════════════════════════════════════════

    #[Route('/exercises', name: 'coach_exercise_list')]
    public function exerciseList(Request $request, ExerciseRepository $exerciseRepo): Response
    {
        $q         = $request->query->get('q', '');
        $isAjax    = $request->isXmlHttpRequest() || $request->query->get('ajax');

        $qb = $exerciseRepo->createQueryBuilder('e')->orderBy('e.nom', 'ASC');

        if ($q !== '') {
            $qb->andWhere('LOWER(e.nom) LIKE :q OR LOWER(e.description) LIKE :q')
               ->setParameter('q', '%' . strtolower($q) . '%');
        }

        $exercises = $qb->getQuery()->getResult();

        if ($isAjax) {
            return $this->render('coach/exercise_list/_list.html.twig', [
                'exercises' => $exercises,
            ]);
        }

        return $this->render('coach/exercise_list.html.twig', [
            'exercises' => $exercises,
            'q'         => $q,
        ]);
    }

    // ═══════════════════════════════════════════
    //  EXERCISE CREATE
    // ═══════════════════════════════════════════

    #[Route('/exercises/create', name: 'coach_exercise_create')]
    public function exerciseCreate(Request $request, EntityManagerInterface $em): Response
    {
        $exercise = new Exercise();
        $form     = $this->createForm(ExerciseType::class, $exercise);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($exercise);
            $em->flush();

            $this->addFlash('success', 'Exercise "' . $exercise->getNom() . '" created successfully!');
            return $this->redirectToRoute('coach_exercise_list');
        }

        return $this->render('coach/exercise_form.html.twig', [
            'form'     => $form->createView(),
            'exercise' => $exercise,
            'isEdit'   => false,
        ]);
    }

    // ═══════════════════════════════════════════
    //  EXERCISE EDIT
    // ═══════════════════════════════════════════

    #[Route('/exercises/{id}/edit', name: 'coach_exercise_edit')]
    public function exerciseEdit(Exercise $exercise, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ExerciseType::class, $exercise);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Exercise "' . $exercise->getNom() . '" updated successfully!');
            return $this->redirectToRoute('coach_exercise_list');
        }

        return $this->render('coach/exercise_form.html.twig', [
            'form'     => $form->createView(),
            'exercise' => $exercise,
            'isEdit'   => true,
        ]);
    }

    // ═══════════════════════════════════════════
    //  EXERCISE DETAIL
    // ═══════════════════════════════════════════

    #[Route('/exercises/{id}', name: 'coach_exercise_detail_db')]
    public function exerciseDetail(Exercise $exercise): Response
    {
        return $this->render('coach/exercise_detail.html.twig', [
            'exercise' => $exercise,
        ]);
    }

    // ═══════════════════════════════════════════
    //  EXERCISE DELETE
    // ═══════════════════════════════════════════

    #[Route('/exercises/{id}/delete', name: 'coach_exercise_delete', methods: ['POST'])]
    public function exerciseDelete(Exercise $exercise, EntityManagerInterface $em): Response
    {
        $em->remove($exercise);
        $em->flush();

        $this->addFlash('success', 'Exercise deleted successfully.');
        return $this->redirectToRoute('coach_exercise_list');
    }

    // ═══════════════════════════════════════════
    //  YOUTUBE SEARCH API
    // ═══════════════════════════════════════════

    #[Route('/youtube-search', name: 'coach_youtube_search')]
    public function youtubeSearch(Request $request, \App\Service\YouTubeService $youtubeService): Response
    {
        $query = $request->query->get('q', '');
        
        if (empty($query)) {
            return $this->json([]);
        }

        $videos = $youtubeService->searchVideos($query, 5);
        
        return $this->json($videos);
    }
}
