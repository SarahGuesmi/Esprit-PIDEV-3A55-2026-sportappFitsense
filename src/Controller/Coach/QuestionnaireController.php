<?php

namespace App\Controller\Coach;

use App\Entity\Questionnaire;
use App\Repository\QuestionnaireRepository;
use App\Repository\FeedbackResponseRepository;
use App\Entity\FeedbackResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/coach/questionnaire')]
#[IsGranted('ROLE_COACH')]
class QuestionnaireController extends AbstractController
{
    #[Route('s', name: 'coach_questionnaire_index')]
    public function index(QuestionnaireRepository $repository, FeedbackResponseRepository $feedbackRepo, EntityManagerInterface $em, Request $request): Response
    {
        $search = trim($request->query->get('search', ''));
        
        if (!empty($search)) {
            $qb = $repository->createQueryBuilder('q')
                ->where('q.type = :type')
                ->andWhere('q.coach = :coach')
                ->andWhere('q.titre LIKE :search')
                ->setParameter('type', 'template')
                ->setParameter('coach', $this->getUser())
                ->setParameter('search', '%' . $search . '%')
                ->orderBy('q.id', 'DESC');
            $quizzes = $qb->getQuery()->getResult();
        } else {
            $quizzes = $repository->findBy([
                'coach' => $this->getUser(),
                'type' => 'template'
            ], ['id' => 'DESC']);
        }
        $workouts = $em->getRepository(\App\Entity\Workout::class)->findAll();

        // Get user responses for feedback from workouts linked to coach's templates
        $userResponses = $repository->findUserResponsesForCoach($this->getUser());

        // Also get feedback from the new FeedbackResponse entity
        // For now, show all feedback responses regardless of coach so that
        // every submitted feedback appears in this dashboard.
        $feedbackResponses = $feedbackRepo->findAll();

        return $this->render('coach/questionnaires.html.twig', [
            'quizzes' => $quizzes,
            'workouts' => $workouts,
            'search' => $search,
            'userResponses' => $userResponses,
            'feedbackResponses' => $feedbackResponses,
        ]);
    }

    #[Route('/feedback/{id}/delete', name: 'coach_feedback_delete', methods: ['POST'])]
    public function deleteFeedback(FeedbackResponse $feedback, EntityManagerInterface $em): Response
    {
        // Allow any coach to delete any feedback
        $em->remove($feedback);
        $em->flush();

        $this->addFlash('success', 'Feedback deleted successfully!');
        return $this->redirectToRoute('coach_questionnaire_index');
    }

    #[Route('/response/{id}/delete', name: 'coach_response_delete', methods: ['POST'])]
    public function deleteResponse(string $id, EntityManagerInterface $em, QuestionnaireRepository $repository): Response
    {
        // Find the questionnaire response and delete it
        $response = $repository->find($id);
        
        if (!$response) {
            throw $this->createNotFoundException('Response not found');
        }

        $em->remove($response);
        $em->flush();

        $this->addFlash('success', 'Response deleted successfully!');
        return $this->redirectToRoute('coach_questionnaire_index');
    }

    #[Route('/create', name: 'coach_questionnaire_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $titre = trim($request->request->get('titre', ''));
        $workoutIds = $request->request->all('workout_ids');
        $options = $request->request->all('options');

        $errors = [];

        // Backend Validation
        if (empty($titre)) {
            $errors['titre'] = 'Quiz title is required.';
        }

        if (empty($workoutIds)) {
            $errors['workouts'] = 'Please select at least one workout.';
        }

        $validOptions = [];
        if (is_array($options)) {
            foreach ($options as $option) {
                if (!empty(trim($option))) {
                    $validOptions[] = htmlspecialchars(trim($option));
                }
            }
        }

        if (count($validOptions) < 1) {
            $errors['options'] = 'The quiz must have at least one valid option.';
        }

        if (!empty($errors)) {
            return $this->json(['success' => false, 'errors' => $errors], 400);
        }

        $quiz = new Questionnaire();
        $quiz->setTitre($titre);
        
        foreach ($workoutIds as $id) {
            $workout = $em->getRepository(\App\Entity\Workout::class)->find($id);
            if ($workout) {
                $quiz->addWorkout($workout);
            }
        }

        $quiz->setOptions($validOptions);
        $quiz->setCoach($this->getUser());
        $quiz->setType('template');

        $em->persist($quiz);
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Quiz added successfully!']);
    }

    #[Route('/{id}/edit', name: 'coach_questionnaire_edit', methods: ['POST'])]
    public function edit(Questionnaire $quiz, Request $request, EntityManagerInterface $em): Response
    {
        if ($quiz->getCoach() !== $this->getUser()) {
            return $this->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $titre = trim($request->request->get('titre', ''));
        $workoutIds = $request->request->all('workout_ids');
        $options = $request->request->all('options');

        $errors = [];

        // Backend Validation
        if (empty($titre)) {
            $errors['titre'] = 'Quiz title is required.';
        }

        if (empty($workoutIds)) {
            $errors['workouts'] = 'Please select at least one workout.';
        }

        $validOptions = [];
        if (is_array($options)) {
            foreach ($options as $option) {
                if (!empty(trim($option))) {
                    $validOptions[] = htmlspecialchars(trim($option));
                }
            }
        }

        if (count($validOptions) < 1) {
            $errors['options'] = 'The quiz must have at least one valid option.';
        }

        if (!empty($errors)) {
            return $this->json(['success' => false, 'errors' => $errors], 400);
        }

        $quiz->setTitre($titre);
        
        // Clear existing workouts
        foreach ($quiz->getWorkouts() as $workout) {
            $quiz->removeWorkout($workout);
        }

        foreach ($workoutIds as $id) {
            $workout = $em->getRepository(\App\Entity\Workout::class)->find($id);
            if ($workout) {
                $quiz->addWorkout($workout);
            }
        }

        $quiz->setOptions($validOptions);

        $em->flush();

        return $this->json(['success' => true, 'message' => 'Quiz modified successfully!']);
    }

    #[Route('/{id}/delete', name: 'coach_questionnaire_delete', methods: ['POST'])]
    public function delete(Questionnaire $quiz, EntityManagerInterface $em): Response
    {
        if ($quiz->getCoach() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $em->remove($quiz);
        $em->flush();

        $this->addFlash('success', 'Quiz deleted successfully!');
        return $this->redirectToRoute('coach_questionnaire_index');
    }

    #[Route('/response', name: 'coach_questionnaire_response', methods: ['POST'])]
    public function submitResponse(Request $request, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true);
        
        $questionnaireId = $data['questionnaire_id'] ?? null;
        $workoutId = $data['workout_id'] ?? null;
        $responseValue = $data['response'] ?? '';

        if (!$questionnaireId || !$workoutId || !$responseValue) {
            return $this->json(['success' => false, 'message' => 'Missing required fields'], 400);
        }

        $questionnaire = $em->getRepository(Questionnaire::class)->find($questionnaireId);
        if (!$questionnaire) {
            return $this->json(['success' => false, 'message' => 'Questionnaire not found'], 404);
        }

        $workout = $em->getRepository(\App\Entity\Workout::class)->find($workoutId);
        if (!$workout) {
            return $this->json(['success' => false, 'message' => 'Workout not found'], 404);
        }

        // Create a new response questionnaire
        $response = new Questionnaire();
        $response->setTitre($questionnaire->getTitre());
        $response->setOptions([$responseValue]);
        $response->setType('response');
        $response->setUser($this->getUser());
        $response->setUserName($this->getUser()->getFirstname() . ' ' . $this->getUser()->getLastname());
        // dateSoumission will be set automatically by PrePersist lifecycle callback
        $response->addWorkout($workout);

        $em->persist($response);
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Feedback submitted successfully!']);
    }
}
