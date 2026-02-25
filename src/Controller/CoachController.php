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
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Service\ExerciseApiService;
use App\Service\YouTubeService;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/coach')]
class CoachController extends AbstractController
{
    // ==================== STATISTICS ====================

    #[Route('/coach/statistics', name: 'coach_statistics')]
    public function statistics(WorkoutRepository $workoutRepo, ExerciseRepository $exerciseRepo): Response
    {
        $totalWorkouts = $workoutRepo->count([]);
        $workouts = $workoutRepo->findAll();
        $totalDuration = array_sum(array_map(fn($w) => $w->getDuree(), $workouts));
        $avgDuration = $workouts ? round($totalDuration / count($workouts), 2) : 0;

        $exercises = $exerciseRepo->findAll();
        $exerciseUsage = [];
        foreach ($exercises as $ex) {
            $exerciseUsage[$ex->getNom()] = count($ex->getWorkouts());
        }
        arsort($exerciseUsage);
        $exerciseLabels = array_keys($exerciseUsage);
        $exerciseData = array_values($exerciseUsage);

        $goalDistribution = [];
        foreach ($workouts as $w) {
            $objectifs = $w->getObjectifs();
            if ($objectifs->isEmpty()) {
                $goalDistribution['Unknown'] = ($goalDistribution['Unknown'] ?? 0) + 1;
            } else {
                foreach ($objectifs as $obj) {
                    $name = $obj->getName();
                    $goalDistribution[$name] = ($goalDistribution[$name] ?? 0) + 1;
                }
            }
        }
        arsort($goalDistribution);
        $goalLabels = array_keys($goalDistribution);
        $goalData = array_values($goalDistribution);

        $levelDistribution = [];
        foreach ($workouts as $w) {
            $level = $w->getNiveau() ?? 'Unknown';
            $levelDistribution[$level] = ($levelDistribution[$level] ?? 0) + 1;
        }
        arsort($levelDistribution);
        $levelLabels = array_keys($levelDistribution);
        $levelData = array_values($levelDistribution);

        return $this->render('coach/statistics.html.twig', [
            'totalWorkouts'  => $totalWorkouts,
            'avgDuration'    => $avgDuration,
            'exerciseLabels' => $exerciseLabels,
            'exerciseData'   => $exerciseData,
            'goalLabels'     => $goalLabels,
            'goalData'       => $goalData,
            'levelLabels'    => $levelLabels,
            'levelData'      => $levelData,
        ]);
    }

    // ==================== AI WORKOUT ====================

    #[Route('/coach/ai-workout', name: 'coach_ai_workout_form')]


    private function generateWorkoutWithExerciseDB(
        string $level,
        string $goal,
        int|string $duration,
        HttpClientInterface $client
    ): array {
        $bodyPartMap = [
            'Weight Loss' => 'cardio',
            'Muscle Gain' => 'chest',
            'Endurance'   => 'back',
        ];

        $bodyPart = $bodyPartMap[$goal] ?? 'chest';

        $response = $client->request('GET', "https://exercisedb.p.rapidapi.com/exercises/bodyPart/{$bodyPart}", [
            'headers' => [
                'X-RapidAPI-Key'  => $_ENV['RAPIDAPI_KEY'],
                'X-RapidAPI-Host' => 'exercisedb.p.rapidapi.com',
            ],
            'query' => ['limit' => 5],
        ]);

        $data = $response->toArray();

        if (empty($data)) {
            throw new \Exception('No exercises found from ExerciseDB.');
        }

        $setsMap = ['Beginner' => 2, 'Intermediate' => 3, 'Advanced' => 4];
        $repsMap = ['Beginner' => 10, 'Intermediate' => 15, 'Advanced' => 20];
        $restMap = ['Beginner' => '60 sec', 'Intermediate' => '45 sec', 'Advanced' => '30 sec'];

        $exercises = [];
        foreach ($data as $ex) {
            $exercises[] = [
                'name'      => ucfirst($ex['name']),
                'sets'      => $setsMap[$level] ?? 3,
                'reps'      => $repsMap[$level] ?? 12,
                'rest'      => $restMap[$level] ?? '45 sec',
                'muscle'    => $ex['target'] ?? '',
                'equipment' => $ex['equipment'] ?? '',
            ];
        }

        return $exercises;
    }

    // ==================== API EXERCISES ====================

    #[Route('/test-api', name: 'coach_test_api')]
    public function testApi(ExerciseApiService $service): Response
    {
        dd([
            'bodyParts'  => $service->getBodyPartList(),
            'targets'    => $service->getTargetList(),
            'equipments' => $service->getEquipmentList(),
            'exercices'  => $service->getExercises(5),
        ]);
    }

    #[Route('/api-exercises', name: 'coach_exercises')]
    public function exercises(Request $request, ExerciseApiService $service): Response
    {
        $bodyPart  = $request->query->get('bodyPart');
        $target    = $request->query->get('target');
        $equipment = $request->query->get('equipment');
        $search    = $request->query->get('search');
        $limit     = 20;
        $offset    = (int) $request->query->get('page', 0) * $limit;

        if ($search) {
            $exercises = $service->searchByName($search, $limit);
        } elseif ($bodyPart) {
            $exercises = $service->getByBodyPart($bodyPart, $limit);
        } elseif ($target) {
            $exercises = $service->getByTarget($target, $limit);
        } elseif ($equipment) {
            $exercises = $service->getByEquipment($equipment, $limit);
        } else {
            $exercises = $service->getExercises($limit, $offset);
        }

        return $this->render('coach/exercises.html.twig', [
            'exercises'       => $exercises,
            'bodyPartList'    => $service->getBodyPartList(),
            'targetList'      => $service->getTargetList(),
            'equipmentList'   => $service->getEquipmentList(),
            'activeBodyPart'  => $bodyPart,
            'activeTarget'    => $target,
            'activeEquipment' => $equipment,
            'search'          => $search,
            'currentPage'     => (int) $request->query->get('page', 0),
        ]);
    }

    // ✅ DOIT être AVANT /api-exercises/{id}
    #[Route('/api-exercises/gif-proxy', name: 'coach_gif_proxy')]
    public function gifProxy(Request $request, HttpClientInterface $client): Response
    {
        $url = $request->query->get('url');

        if (!$url || !str_contains($url, 'rapidapi.com')) {
            return new Response('Forbidden', 403);
        }

        try {
            $response = $client->request('GET', $url, [
                'headers' => [
                    'X-RapidAPI-Key'  => $_ENV['RAPIDAPI_KEY'],
                    'X-RapidAPI-Host' => 'exercisedb.p.rapidapi.com',
                ]
            ]);

            return new Response(
                $response->getContent(),
                200,
                ['Content-Type' => 'image/gif']
            );
            } catch (\Exception $e) {
                return new Response('ERREUR: ' . $e->getMessage(), 404);
            }
    }

    // ✅ DOIT être APRÈS gif-proxy
#[Route('/api-exercises/{id}', name: 'coach_exercise_detail')]
public function exerciseDetail(string $id, ExerciseApiService $service): Response
{
    $exercise = $service->getExerciseById($id);

    if (empty($exercise) || !isset($exercise['name'])) {
        $exercise = [
            'id'               => $id,
            'name'             => 'Exercice introuvable',
            'bodyPart'         => 'N/A',
            'target'           => 'N/A',
            'equipment'        => 'N/A',
            'difficulty'       => 'N/A',
            'category'         => 'N/A',
            'description'      => 'Aucune description disponible.',
            'secondaryMuscles' => [],
            'instructions'     => [],
            'gifUrl'           => null,
        ];
    }

    // ⚡ Ajout pour sécuriser les clés manquantes
    $exercise['gifUrl'] = $exercise['gifUrl'] ?? null;
    $exercise['instructions'] = $exercise['instructions'] ?? [];
    $exercise['secondaryMuscles'] = $exercise['secondaryMuscles'] ?? [];

    return $this->render('coach/exercise_detail.html.twig', [
        'exercise' => $exercise,
    ]);
}

    // ==================== WORKOUT CATALOG ====================

    #[Route('/workouts', name: 'coach_workout_catalog')]
    #[IsGranted('ROLE_COACH')]
    public function workoutCatalog(
        Request $request,
        EntityManagerInterface $em,
        ExerciseRepository $exerciseRepository
    ): Response {
        $q                 = $request->query->get('q', '');
        $selectedObjectifs = $request->query->all('objectifs');

        $queryBuilder = $em->getRepository(Workout::class)
            ->createQueryBuilder('w')
            ->leftJoin('w.objectifs', 'o')
            ->addSelect('o');

        if (!empty($q)) {
            $queryBuilder
                ->andWhere('w.nom LIKE :q OR w.description LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        if (!empty($selectedObjectifs)) {
            $queryBuilder
                ->andWhere('o.id IN (:ids)')
                ->setParameter('ids', $selectedObjectifs);
        }

        $workouts = $queryBuilder->getQuery()->getResult();

        if (
            $request->isXmlHttpRequest()
            || $request->headers->get('X-Requested-With') === 'XMLHttpRequest'
            || $request->query->get('ajax')
        ) {
            return $this->render('coach/workout_catalog/_list.html.twig', [
                'workouts' => $workouts,
            ]);
        }

        $objectifs     = $em->getRepository(ObjectifSportif::class)->findAll();
        $exerciseCount = $exerciseRepository->count([]);

        return $this->render('coach/workout_catalog.html.twig', [
            'workouts'          => $workouts,
            'exerciseCount'     => $exerciseCount,
            'objectifs'         => $objectifs,
            'selectedObjectifs' => $selectedObjectifs,
            'q'                 => $q,
        ]);
    }

    // ==================== EXERCISE MANAGEMENT ====================

    #[Route('/exercises', name: 'coach_exercise_list')]
    public function exerciseList(Request $request, ExerciseRepository $exerciseRepository): Response
    {
        $q = $request->query->get('q', '');

        $queryBuilder = $exerciseRepository->createQueryBuilder('e')
            ->orderBy('e.nom', 'ASC');

        if (!empty($q)) {
            $queryBuilder
                ->andWhere('e.nom LIKE :q OR e.description LIKE :q OR e.type LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        $exercises = $queryBuilder->getQuery()->getResult();

        if (
            $request->isXmlHttpRequest()
            || $request->headers->get('X-Requested-With') === 'XMLHttpRequest'
            || $request->query->get('ajax')
        ) {
            return $this->render('coach/exercise_list/_list.html.twig', [
                'exercises' => $exercises,
            ]);
        }

        return $this->render('coach/exercise_list.html.twig', [
            'exercises' => $exercises,
        ]);
    }

   // ==================== YOUTUBE SEARCH ====================

#[Route('/youtube-search', name: 'coach_youtube_search')]
public function youtubeSearch(
    Request $request,
    YouTubeService $youTubeService
): JsonResponse {
    $query = $request->query->get('q', '');

    if (strlen($query) < 2) {
        return $this->json([]);
    }

    $videos = $youTubeService->searchVideos($query, 5);

    return $this->json($videos);
}

// ==================== EXERCISE MANAGEMENT ====================

#[Route('/exercise/create', name: 'coach_exercise_create')]
public function exerciseCreate(Request $request, EntityManagerInterface $em): Response
{
    $exercise = new Exercise();
    $form     = $this->createForm(ExerciseType::class, $exercise);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        if (!$exercise->getDescription()) {
            $exercise->setDescription('Description par défaut');
        }
        $em->persist($exercise);
        $em->flush();

        $this->addFlash('success', 'Exercice créé avec succès !');
        return $this->redirectToRoute('coach_exercise_list');
    }

    return $this->render('coach/exercise_form.html.twig', [
        'form'   => $form->createView(),
        'isEdit' => false,
    ]);
}

#[Route('/exercise/{id}/edit', name: 'coach_exercise_edit')]
public function exerciseEdit(Exercise $exercise, Request $request, EntityManagerInterface $em): Response
{
    $form = $this->createForm(ExerciseType::class, $exercise);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->flush();
        $this->addFlash('success', 'Exercice modifié avec succès !');
        return $this->redirectToRoute('coach_exercise_list');
    }

    return $this->render('coach/exercise_form.html.twig', [
        'form'     => $form->createView(),
        'exercise' => $exercise,
        'isEdit'   => true,
    ]);
}
    #[Route('/exercise/{id}/delete', name: 'coach_exercise_delete', methods: ['POST'])]
    public function exerciseDelete(Exercise $exercise, EntityManagerInterface $em): Response
    {
        $em->remove($exercise);
        $em->flush();

        $this->addFlash('success', 'Exercice supprimé avec succès !');
        return $this->redirectToRoute('coach_exercise_list');
    }

    // ==================== WORKOUT MANAGEMENT ====================

#[Route('/workout/create', name: 'coach_workout_create')]
public function workoutCreate(Request $request, EntityManagerInterface $em, ExerciseRepository $exerciseRepository): Response
{
    $exerciseCount = $exerciseRepository->count([]);

    if ($exerciseCount === 0) {
        $this->addFlash('warning', 'Vous devez créer au moins un exercice avant de créer un workout.');
        return $this->redirectToRoute('coach_exercise_create');
    }

    $workout = new Workout();
    $form    = $this->createForm(WorkoutType::class, $workout);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // ✅ Définir le niveau avant persist, sans dépendre de $aiData
        if (!$workout->getNiveau()) {
            $workout->setNiveau('beginner');
        }

        $em->persist($workout);
        $em->flush();

        $this->addFlash('success', 'Workout créé avec succès !');
        return $this->redirectToRoute('coach_workout_catalog');
    }

    return $this->render('coach/workout_form.html.twig', [
        'form'   => $form->createView(),
        'isEdit' => false,
    ]);
}

    #[Route('/workout/{id}/edit', name: 'coach_workout_edit')]
    public function workoutEdit(Workout $workout, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(WorkoutType::class, $workout);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Workout modifié avec succès !');
            return $this->redirectToRoute('coach_workout_catalog');
        }

        return $this->render('coach/workout_form.html.twig', [
            'form'    => $form->createView(),
            'workout' => $workout,
            'isEdit'  => true,
        ]);
    }

    #[Route('/workout/{id}/delete', name: 'coach_workout_delete', methods: ['POST'])]
    public function workoutDelete(Workout $workout, EntityManagerInterface $em): Response
    {
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