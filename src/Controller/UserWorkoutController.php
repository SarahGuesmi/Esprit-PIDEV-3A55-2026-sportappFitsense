<?php

namespace App\Controller;

use App\Repository\ExerciseRepository;
use App\Repository\WorkoutRepository;
use App\Repository\ObjectifSportifRepository;
use App\Repository\QuestionnaireRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\UserExerciseProgressRepository;
use App\Entity\UserExerciseProgress;
use App\Entity\FeedbackResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\FeedbackResponseRepository;


#[Route('/user')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
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
        Request $request,
        WorkoutRepository $workoutRepository,
        UserExerciseProgressRepository $progressRepo,
        QuestionnaireRepository $questionnaireRepo,
        EntityManagerInterface $em,
        FeedbackResponseRepository $feedbackRepo
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

        // Check for feedback submission via query parameters (GET request)
        $rating = $request->query->get('rating');
        $workoutIdFromQuery = $request->query->get('workout_id');
        $workoutTitle = $request->query->get('workout_title');
        $comment = $request->query->get('comment');

        if ($rating && $workoutIdFromQuery) {
            // Create a synthetic request to process feedback
            $feedbackData = [
                'workout_id' => $workoutIdFromQuery,
                'workout_title' => $workoutTitle,
                'rating' => $rating,
                'comment' => $comment ?? '',
            ];

            // Process the feedback
            $feedbackRequest = new Request(
                [],
                $feedbackData,
                [],
                [],
                [],
                ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'],
                http_build_query($feedbackData)
            );

            $result = $this->processFeedback($feedbackRequest, $em);
            $data = json_decode($result->getContent(), true);

            if ($data['success'] ?? false) {
                $this->addFlash('success', 'Your feedback has been submitted successfully!');
            } else {
                $this->addFlash('error', $data['message'] ?? 'Error submitting feedback');
            }

            // Redirect to remove query parameters from URL
            return $this->redirectToRoute('user_workout_view', ['id' => $id]);
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

        // Check if the current user has already submitted feedback for this workout
        $existingFeedback = $feedbackRepo->findOneBy([
            'user'    => $user,
            'workout' => $workout,
        ]);

        // Get questionnaires linked to this workout by title
        $questionnaires = $questionnaireRepo->findByWorkoutTitle($workout->getNom());

        return $this->render('user/workout_view.html.twig', [
            'workout'         => $workout,
            'doneExerciseIds' => $doneExerciseIds,
            'allDone'         => $allDone,
            'doneCount'       => count($doneExerciseIds),
            'totalExercises'  => $totalExercises,
            'questionnaires'  => $questionnaires,
            'existingFeedback'=> $existingFeedback,
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

    #[Route('/feedback/submit', name: 'user_feedback_submit', methods: ['POST'])]
    public function submitFeedback(Request $request, EntityManagerInterface $em): JsonResponse
    {
        return $this->processFeedback($request, $em);
    }

    #[Route('/feedback/submit-get', name: 'user_feedback_submit_get', methods: ['GET'])]
    public function submitFeedbackGet(Request $request, EntityManagerInterface $em): Response
    {
        // Create a fake POST request from GET parameters
        $feedbackRequest = new Request(
            $request->query->all(),
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($request->query->all())
        );
        
        $result = $this->processFeedback($feedbackRequest, $em);
        
        // If it's a JSON response, return it directly
        if ($result instanceof JsonResponse) {
            $data = json_decode($result->getContent(), true);
            if ($data['success'] ?? false) {
                // Redirect back to workout with success message
                $workoutId = $request->query->get('workout_id');
                $this->addFlash('success', 'Your feedback is submitted successfully!');
                return $this->redirectToRoute('user_workout_view', ['id' => $workoutId]);
            } else {
                $this->addFlash('error', $data['message'] ?? 'Error submitting feedback');
                return $this->redirectToRoute('user_workout_view', ['id' => $request->query->get('workout_id')]);
            }
        }
        
        return $result;
    }

    private function processFeedback(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            // Handle both JSON POST and form data
            $content = $request->getContent();
            if (!empty($content)) {
                $data = json_decode($content, true);
            } else {
                $data = $request->request->all();
            }
            
            if (json_last_error() !== JSON_ERROR_NONE && empty($data)) {
                return $this->json(['success' => false, 'message' => 'Invalid request data'], 400);
            }
            
            $workoutId = $data['workout_id'] ?? null;
            $workoutTitle = $data['workout_title'] ?? null;
            $rating = $data['rating'] ?? null;
            $comment = $data['comment'] ?? '';

            if (!$workoutId || !$rating) {
                return $this->json(['success' => false, 'message' => 'Missing required fields: workout_id=' . $workoutId . ', rating=' . $rating], 400);
            }

            $workout = $em->getRepository(\App\Entity\Workout::class)->find($workoutId);
            if (!$workout) {
                return $this->json(['success' => false, 'message' => 'Workout not found with id: ' . $workoutId], 404);
            }

            $user = $this->getUser();

            // Prevent duplicate feedback: one FeedbackResponse per user+workout
            $existingFeedback = $em->getRepository(FeedbackResponse::class)
                ->findOneBy(['user' => $user, 'workout' => $workout]);

            if ($existingFeedback) {
                return $this->json([
                    'success' => false,
                    'message' => 'You have already submitted feedback for this workout.',
                ], 400);
            }

            // Get the coach from the workout
            $coach = $workout->getCoach();

            // Create a new feedback response using the new entity
            $feedbackResponse = new FeedbackResponse();
            $feedbackResponse->setUser($user);
            $feedbackResponse->setWorkout($workout);
            $feedbackResponse->setRating($rating);
            $feedbackResponse->setComment($comment);
            $feedbackResponse->setCoach($coach);

            $em->persist($feedbackResponse);

            // Also create a questionnaire for backward compatibility
            $questionnaire = new \App\Entity\Questionnaire();
            $questionnaire->setTitre('Feedback: ' . ($workoutTitle ?? $workout->getNom()));
            $questionnaire->setOptions([$rating]);
            $questionnaire->setCommentaire($comment);
            $questionnaire->setType('response');
            $questionnaire->setUser($user);
            // Use firstname/lastname from the current User entity
            $questionnaire->setUserName(trim(($user->getFirstname() ?? '') . ' ' . ($user->getLastname() ?? '')));
            $questionnaire->setDateSoumission(new \DateTimeImmutable());
            $questionnaire->addWorkout($workout);

            $em->persist($questionnaire);
            $em->flush();

            return $this->json(['success' => true, 'message' => 'Feedback submitted successfully!']);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}
