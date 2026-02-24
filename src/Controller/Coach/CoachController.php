<?php

namespace App\Controller\Coach;

use App\Entity\Exercise;
use App\Entity\Recommendation;
use App\Entity\RecommendedExercise;
use App\Entity\EtatMental;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/coach')]
#[IsGranted('ROLE_COACH')]
class CoachController extends AbstractController
{
    #[Route('/dashboard', name: 'coach_dashboard')]
    public function index(): Response
    {
        return $this->render('coach/dashboard.html.twig', []);
    }

    #[Route('/users', name: 'coach_users_index')]
    public function users(Request $request, EntityManagerInterface $em): Response
    {
        $currentUser = $this->getUser();
        $q = $request->query->get('q', '');
        $status = $request->query->get('status', '');
        $role = $request->query->get('role', '');
        
        $queryBuilder = $em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.id != :currentUserId')
            ->setParameter('currentUserId', $currentUser->getId())
            ->orderBy('u.dateCreation', 'DESC');

        if (!empty($q)) {
            $queryBuilder->andWhere('u.firstname LIKE :q OR u.lastname LIKE :q OR u.email LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        if (!empty($status)) {
            $queryBuilder->andWhere('u.accountStatus = :status')
                ->setParameter('status', $status);
        }

        if (!empty($role)) {
            $queryBuilder->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%' . $role . '%');
        }

        $users = $queryBuilder->getQuery()->getResult();

        if ($request->isXmlHttpRequest() || $request->headers->get('X-Requested-With') === 'XMLHttpRequest' || $request->query->get('ajax')) {
            return $this->render('coach/users/_table.html.twig', [
                'users' => $users,
            ]);
        }

        return $this->render('coach/users/index_coach.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/mental-health', name: 'coach_mental_health_index')]
    public function mentalHealth(Request $request, EntityManagerInterface $em): Response
    {
        $q = $request->query->get('q', '');
        $status = $request->query->get('status', '');

        $queryBuilder = $em->getRepository(EtatMental::class)
            ->createQueryBuilder('em')
            ->join('em.user', 'u')
            ->orderBy('em.createdAt', 'DESC');

        if (!empty($q)) {
            $queryBuilder->andWhere('u.firstname LIKE :q OR u.lastname LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        if (!empty($status)) {
            $queryBuilder->andWhere('em.status = :status')
                ->setParameter('status', $status);
        }

        $evaluations = $queryBuilder->getQuery()->getResult();

        if ($request->isXmlHttpRequest() || $request->headers->get('X-Requested-With') === 'XMLHttpRequest' || $request->query->get('ajax')) {
            return $this->render('coach/mental_health/_table.html.twig', [
                'evaluations' => $evaluations,
            ]);
        }

        return $this->render('coach/mental_health/index.html.twig', [
            'evaluations' => $evaluations,
        ]);
    }

    #[Route('/mental-health/recommendations', name: 'coach_mental_health_recommendations_list')]
    public function listRecommendations(EntityManagerInterface $em): Response
    {
        $recommendations = $em->getRepository(Recommendation::class)
            ->findBy(['coach' => $this->getUser()], ['createdAt' => 'DESC']);

        return $this->render('coach/mental_health/recommendations_list.html.twig', [
            'recommendations' => $recommendations,
        ]);
    }

    #[Route('/mental-health/recommendation/add', name: 'coach_mental_health_recommend_add', methods: ['POST'])]
    public function addRecommendation(Request $request, EntityManagerInterface $em, MailerInterface $mailer, LoggerInterface $logger): Response
    {
        $userId = $request->request->get('user_id');
        $exerciseTitles = $request->request->all('exercise_titles');
        $exerciseDescs = $request->request->all('exercise_descriptions');
        $exerciseDurations = $request->request->all('exercise_durations');
        $notes = $request->request->get('notes');

        $user = $em->getRepository(User::class)->find($userId);
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('coach_mental_health_index');
        }

        $recommendation = new Recommendation();
        $recommendation->setCoach($this->getUser());
        $recommendation->setUser($user);
        $recommendation->setNotes($notes);

        if (!empty($exerciseTitles)) {
            foreach ($exerciseTitles as $index => $title) {
                if (empty($title)) continue;
                
                $exercise = new RecommendedExercise();
                $exercise->setTitle($title);
                $exercise->setDescription($exerciseDescs[$index] ?? '');
                $exercise->setDuration((int)($exerciseDurations[$index] ?? 0));
                
                $recommendation->addRecommendedExercise($exercise);
            }
        }

        $em->persist($recommendation);
        $em->flush();

        // Send Email Notification
        try {
            $email = (new TemplatedEmail())
                ->from(new Address('sarahguesmi223@gmail.com', 'FitSense Wellness'))
                ->to($user->getEmail())
                ->subject('New Wellness Recommendation from your Coach')
                ->htmlTemplate('emails/recommendation_email.html.twig')
                ->context([
                    'user_name' => $user->getFirstname(),
                    'coach_name' => $this->getUser()->getFirstname() . ' ' . $this->getUser()->getLastname(),
                    'notes' => $notes,
                    'exercises' => $recommendation->getRecommendedExercises()
                ]);

            $mailer->send($email);
        } catch (\Exception $e) {
            $logger->error('Failed to send recommendation email: ' . $e->getMessage(), [
                'exception' => $e
            ]);
        }

        $this->addFlash('success', 'Recommendation saved successfully.');
        return $this->redirectToRoute('coach_mental_health_recommendations_list');
    }

    #[Route('/mental-health/recommendation/delete/{id}', name: 'coach_mental_health_recommend_delete', methods: ['POST'])]
    public function deleteRecommendation(Recommendation $recommendation, EntityManagerInterface $em): Response
    {
        if ($recommendation->getCoach() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $em->remove($recommendation);
        $em->flush();

        $this->addFlash('success', 'Recommendation deleted.');
        return $this->redirectToRoute('coach_mental_health_recommendations_list');
    }

    #[Route('/mental-health/recommendation/edit/{id}', name: 'coach_mental_health_recommend_edit')]
    public function editRecommendation(Recommendation $recommendation, Request $request, EntityManagerInterface $em): Response
    {
        if ($recommendation->getCoach() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $exerciseTitles = $request->request->all('exercise_titles');
            $exerciseDescs = $request->request->all('exercise_descriptions');
            $exerciseDurations = $request->request->all('exercise_durations');
            $notes = $request->request->get('notes');

            $recommendation->setNotes($notes);

            // Simple sync: remove old ones, add new ones
            foreach ($recommendation->getRecommendedExercises() as $oldEx) {
                $em->remove($oldEx);
            }
            $em->flush(); 

            if (!empty($exerciseTitles)) {
                foreach ($exerciseTitles as $index => $title) {
                    if (empty($title)) continue;
                    
                    $exercise = new RecommendedExercise();
                    $exercise->setTitle($title);
                    $exercise->setDescription($exerciseDescs[$index] ?? '');
                    $exercise->setDuration((int)($exerciseDurations[$index] ?? 0));
                    
                    $recommendation->addRecommendedExercise($exercise);
                }
            }

            $em->flush();

            $this->addFlash('success', 'Recommendation updated successfully.');
            return $this->redirectToRoute('coach_mental_health_recommendations_list');
        }

        return $this->render('coach/mental_health/edit_recommendation.html.twig', [
            'recommendation' => $recommendation,
        ]);
    }
}
