<?php

namespace App\Controller\Coach;

use App\Entity\Questionnaire;
use App\Repository\QuestionnaireRepository;
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
    public function index(QuestionnaireRepository $repository, EntityManagerInterface $em, Request $request): Response
    {
        $search = $request->query->get('search', '');
        
        $qb = $repository->createQueryBuilder('q')
            ->where('q.type = :type')
            ->andWhere('q.coach = :coach')
            ->setParameter('type', 'template')
            ->setParameter('coach', $this->getUser())
            ->orderBy('q.id', 'DESC');

        if (!empty($search)) {
            $qb->andWhere('q.titre LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $quizzes = $qb->getQuery()->getResult();
        $workouts = $em->getRepository(\App\Entity\Workout::class)->findAll();

        return $this->render('coach/questionnaires.html.twig', [
            'quizzes' => $quizzes,
            'workouts' => $workouts,
            'search' => $search,
        ]);
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
}
