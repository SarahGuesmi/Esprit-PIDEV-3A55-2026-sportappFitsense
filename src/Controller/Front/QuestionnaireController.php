<?php
// src/Controller/Front/QuestionnaireController.php
namespace App\Controller\Front;

use App\Entity\Questionnaire;
use App\Entity\Workout;
use App\Form\QuestionnaireType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuestionnaireController extends AbstractController
{
    #[Route('/questionnaire', name: 'app_questionnaire')]
    public function index(): Response
    {
        // Redirect to a default workout or list of workouts
        // For now, redirect to home or handle appropriately
        return $this->redirectToRoute('app_home');
    }

    #[Route('/questionnaire/test', name: 'app_questionnaire_test_root')]
    public function testWithoutWorkout(EntityManagerInterface $em): Response
    {
        // Trouve n'importe quel workout existant (par ex. celui des fixtures)
        $workout = $em->getRepository(Workout::class)->findOneBy([]);

        if (!$workout) {
            throw $this->createNotFoundException('Aucune séance trouvée pour le questionnaire.');
        }

        return $this->redirectToRoute('app_questionnaire_test', [
            'workoutId' => $workout->getId(),
        ]);
    }

    #[Route('/questionnaire/test/{workoutId}', name: 'app_questionnaire_test')]
    public function test(Request $request, EntityManagerInterface $em, int $workoutId): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('auth_sign_in');
        }

        $workout = $em->getRepository(Workout::class)->find($workoutId);
        if (!$workout) {
            throw $this->createNotFoundException('Workout not found');
        }

        $questionnaire = new Questionnaire();
        $questionnaire->setUser($this->getUser());
        $questionnaire->setWorkout($workout);
        $questionnaire->setUserName($this->getUser()->getFirstname() . ' ' . $this->getUser()->getLastname());

        $form = $this->createForm(QuestionnaireType::class, $questionnaire, [
            'action' => $this->generateUrl('app_questionnaire_test', ['workoutId' => $workoutId]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $questionnaire->setDateSoumission(new \DateTimeImmutable());

            $em->persist($questionnaire);
            $em->flush();

            return $this->redirectToRoute('app_questionnaire_success');
        }

        return $this->render('questionnaire/test.html.twig', ['form' => $form->createView(), 'workout' => $workout]);
    }

    #[Route('/questionnaire/success', name: 'app_questionnaire_success')]
    public function success(): Response
    {
        return $this->render('questionnaire/success.html.twig');
    }
}
