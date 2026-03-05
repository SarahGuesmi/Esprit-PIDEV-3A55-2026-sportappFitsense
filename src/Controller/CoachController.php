<?php

namespace App\Controller;

use App\Service\ExerciseApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver\RequestAttributeValueResolver;

#[Route('/coach')]
class CoachController extends AbstractController
{
    // ✅ Injection propre de la clé API via le constructeur
    public function __construct(
        private readonly string $rapidApiKey
    ) {}

    // ==================== AI WORKOUT ====================

    #[Route('/ai-workout', name: 'coach_ai_workout_form', methods: ['GET', 'POST'])]
    public function aiWorkoutForm(Request $request, HttpClientInterface $client): Response
    {
        $exercises = [];
        $error     = null;

        if ($request->isMethod('POST')) {
            $level    = $request->request->get('level', 'Beginner');
            $goal     = $request->request->get('goal', 'Muscle Gain');
            $duration = $request->request->get('duration', 30);

            try {
                $exercises = $this->generateWorkoutWithExerciseDB($level, $goal, $duration, $client);
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }

        return $this->render('coach/ai_workout.html.twig', [
            'exercises' => $exercises,
            'error'     => $error,
        ]);
    }

    // ==================== MÉTHODE PRIVÉE : Génération Workout ====================

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

        $response = $client->request(
            'GET',
            "https://exercisedb.p.rapidapi.com/exercises/bodyPart/{$bodyPart}",
            [
                'headers' => [
                    'X-RapidAPI-Key'  => $this->rapidApiKey, // ✅ injection propre
                    'X-RapidAPI-Host' => 'exercisedb.p.rapidapi.com',
                ],
                'query' => ['limit' => 5],
            ]
        );

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
                'name'      => ucfirst($ex['name'] ?? 'Unknown'),
                'sets'      => $setsMap[$level]  ?? 3,
                'reps'      => $repsMap[$level]  ?? 12,
                'rest'      => $restMap[$level]  ?? '45 sec',
                'muscle'    => $ex['target']    ?? 'N/A',
                'equipment' => $ex['equipment'] ?? 'N/A',
            ];
        }

        return $exercises;
    }

    // ==================== API EXERCISES ====================

    /**
     * ⚠️  Route de debug — À SUPPRIMER EN PRODUCTION
     */
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

        $exercises = match (true) {
            (bool) $search    => $service->searchByName($search, $limit),
            (bool) $bodyPart  => $service->getByBodyPart($bodyPart, $limit),
            (bool) $target    => $service->getByTarget($target, $limit),
            (bool) $equipment => $service->getByEquipment($equipment, $limit),
            default           => $service->getExercises($limit, $offset),
        };

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

    /**
     * ✅ DOIT être déclaré AVANT /api-exercises/{id}
     * Proxy pour charger les GIFs RapidAPI sans erreur CORS
     */
    #[Route('/api-exercises/gif-proxy', name: 'coach_gif_proxy')]
    public function gifProxy(Request $request, HttpClientInterface $client): Response
    {
        $url = $request->query->get('url');

        if (!$url) {
            return new Response('URL parameter missing', Response::HTTP_BAD_REQUEST);
        }

        // Les GIFs de ExerciseDB sont maintenant publics et ne nécessitent plus de headers
        // On peut les charger directement sans proxy
        if (str_contains($url, 'v2.exercisedb.io') || str_contains($url, 'exercisedb.io')) {
            try {
                $response = $client->request('GET', $url);
                
                return new Response(
                    $response->getContent(),
                    Response::HTTP_OK,
                    ['Content-Type' => 'image/gif']
                );
            } catch (\Exception $e) {
                return new Response('Error loading GIF: ' . $e->getMessage(), Response::HTTP_NOT_FOUND);
            }
        }

        // Pour les anciennes URLs RapidAPI
        if (str_contains($url, 'rapidapi.com')) {
            try {
                $response = $client->request('GET', $url, [
                    'headers' => [
                        'X-RapidAPI-Key'  => $this->rapidApiKey,
                        'X-RapidAPI-Host' => 'exercisedb.p.rapidapi.com',
                    ],
                ]);

                return new Response(
                    $response->getContent(),
                    Response::HTTP_OK,
                    ['Content-Type' => 'image/gif']
                );
            } catch (\Exception $e) {
                return new Response('Error loading GIF from RapidAPI: ' . $e->getMessage(), Response::HTTP_NOT_FOUND);
            }
        }

        return new Response('Invalid URL', Response::HTTP_FORBIDDEN);
    }

    /**
     * ✅ DOIT être déclaré APRÈS gif-proxy
     */
#[Route('/api-exercises/{exerciseId}', name: 'coach_exercise_detail')]
public function exerciseDetail(
    #[ValueResolver(RequestAttributeValueResolver::class)] string $exerciseId,
    ExerciseApiService $service
): Response {
    $exercise = $service->getExerciseById($exerciseId);

    if (empty($exercise) || !isset($exercise['name'])) {
        $exercise = [
            'id'               => $exerciseId,
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

    $exercise['gifUrl']           = $exercise['gifUrl']           ?? null;
    $exercise['instructions']     = $exercise['instructions']     ?? [];
    $exercise['secondaryMuscles'] = $exercise['secondaryMuscles'] ?? [];

    return $this->render('coach/exercise_detail.html.twig', [
        'exercise' => $exercise,
    ]);
}


    
}