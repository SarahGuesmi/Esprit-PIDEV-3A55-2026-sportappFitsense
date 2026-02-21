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


#[Route('/coach')]
class CoachController extends AbstractController
{
    // ==================== WORKOUT CATALOG ====================


    #[Route('/coach/ai-workout', name: 'coach_ai_workout_form')]
public function aiForm(): Response
{
    return $this->render('coach/ai_workout/form.html.twig');
}

#[Route('/coach/ai-workout/generate', name: 'coach_ai_workout_generate', methods: ['POST'])]
public function generateAI(Request $request): Response
{
    $level = $request->request->get('level');
    $goal = $request->request->get('goal');
    $duration = $request->request->get('duration');

    // Simulation IA (pour soutenance si pas d'API)
    $workout = $this->generateFakeWorkout($level, $goal, $duration);
    if ($goal === 'Muscle Gain') {
    $rest = '90 sec';
}

if ($goal === 'Weight Loss') {
    $rest = '30 sec';
}

if ($goal === 'Endurance') {
    $reps = $duration . ' sec';
}
    return $this->render('coach/ai_workout/result.html.twig', [
        'workout' => $workout,
        'level' => $level,
        'goal' => $goal,
        'duration' => $duration
    ]);
}

private function generateFakeWorkout($level, $goal, $duration)
{
    // Base d'exercices par objectif
    $exercisesByGoal = [
        'Weight Loss' => [
            'Jumping Jacks',
            'Burpees',
            'Mountain Climbers',
            'High Knees',
            'Jump Rope',
            'Squat Jumps'
        ],
        'Muscle Gain' => [
            'Push-ups',
            'Squats',
            'Lunges',
            'Pull-ups',
            'Plank',
            'Dumbbell Press'
        ],
        'Endurance' => [
            'Running in place',
            'Cycling',
            'Jump Rope',
            'Step-ups',
            'Wall Sit',
            'Plank'
        ]
    ];

    // Difficulté selon le niveau
    switch ($level) {
        case 'Beginner':
            $sets = 2;
            $reps = 10;
            $rest = '60 sec';
            break;

        case 'Intermediate':
            $sets = 3;
            $reps = 15;
            $rest = '45 sec';
            break;

        case 'Advanced':
            $sets = 4;
            $reps = 20;
            $rest = '30 sec';
            break;
    }

    // Nombre d’exercices selon la durée
    if ($duration <= 20) {
        $exerciseCount = 3;
    } elseif ($duration <= 40) {
        $exerciseCount = 4;
    } else {
        $exerciseCount = 6;
    }

    // Récupérer la liste selon l’objectif
    $available = $exercisesByGoal[$goal] ?? [];

    // Mélanger pour avoir un résultat différent chaque fois
    shuffle($available);

    $selected = array_slice($available, 0, $exerciseCount);

    $workout = [];

    foreach ($selected as $exercise) {
        $workout[] = [
            'name' => $exercise,
            'sets' => $sets,
            'reps' => $goal === 'Endurance' ? $duration . ' sec' : $reps,
            'rest' => $rest
        ];
    }

    return $workout;
}




#[Route('/coach/ai-workout/save', name: 'coach_ai_workout_save', methods: ['POST'])]
public function saveAIWorkout(Request $request, EntityManagerInterface $em): Response
{
    $level = $request->request->get('level');
    $goal = $request->request->get('goal');
    $duration = $request->request->get('duration');

    $workout = new Workout();
    $workout->setNom("AI Workout - $goal ($level)");
    $workout->setDuree((int) $duration); // ✔ correction ici
    $workout->setNiveau('beginner'); // valeur par défaut
    $em->persist($workout);
    $em->flush();

    $this->addFlash('success', 'AI Workout saved successfully');

    return $this->redirectToRoute('coach_workout_catalog');
}

private function generateAIWorkoutWithOpenAI($level, $goal, $duration, HttpClientInterface $client)
{
    $prompt = "Create a $duration-minute $goal workout for a $level level. 
    Give 4 to 6 exercises with sets and reps.";

    $response = $client->request('POST', 'https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $_ENV['OPENAI_API_KEY'],
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'model' => 'gpt-4.1-mini',
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 300
        ]
    ]);

    $data = $response->toArray();

    return $data['choices'][0]['message']['content'];
}



    #[Route('/workouts', name: 'coach_workout_catalog')]
    public function workoutCatalog(
        Request $request,
        EntityManagerInterface $em,
        ExerciseRepository $exerciseRepository
    ): Response {
        $q                = $request->query->get('q', '');
        $selectedObjectifs = $request->query->all('objectifs');

        $queryBuilder = $em->getRepository(Workout::class)
            ->createQueryBuilder('w')
            ->leftJoin('w.objectifs', 'o')
            ->addSelect('o');

        // Filtre texte (nom ou description)
        if (!empty($q)) {
            $queryBuilder
                ->andWhere('w.nom LIKE :q OR w.description LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        // Filtre par objectifs
        if (!empty($selectedObjectifs)) {
            $queryBuilder
                ->andWhere('o.id IN (:ids)')
                ->setParameter('ids', $selectedObjectifs);
        }

        $workouts = $queryBuilder->getQuery()->getResult();

        // Réponse AJAX → retourner uniquement la liste partielle
        if (
            $request->isXmlHttpRequest()
            || $request->headers->get('X-Requested-With') === 'XMLHttpRequest'
            || $request->query->get('ajax')
        ) {
            return $this->render('coach/workout_catalog/_list.html.twig', [
                'workouts' => $workouts,
            ]);
        }

        $objectifs        = $em->getRepository(ObjectifSportif::class)->findAll();
        $exerciseCount    = $exerciseRepository->count([]);

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

    #[Route('/exercise/create', name: 'coach_exercise_create')]
    public function exerciseCreate(
        Request $request,
        EntityManagerInterface $em
    ): Response {
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
            'form'     => $form->createView(),
            'exercise' => $exercise,
            'isEdit'   => true,
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
        $exerciseCount = $exerciseRepository->count([]);

        if ($exerciseCount === 0) {
            $this->addFlash('warning', 'Vous devez créer au moins un exercice avant de créer un workout.');
            return $this->redirectToRoute('coach_exercise_create');
        }

        $workout = new Workout();
        $form    = $this->createForm(WorkoutType::class, $workout);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($workout);
            $workout->setNiveau($aiData['niveau'] ?? 'beginner');
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
            'form'    => $form->createView(),
            'workout' => $workout,
            'isEdit'  => true,
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