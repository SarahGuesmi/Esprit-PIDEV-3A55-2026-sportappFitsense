<?php

namespace App\Controller\Coach;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
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
    public function users(EntityManagerInterface $em): Response
    {
        $currentUser = $this->getUser();
        
        $users = $em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.id != :currentUserId')
            ->setParameter('currentUserId', $currentUser->getId())
            ->orderBy('u.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('coach/users/index_coach.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/mental-health', name: 'coach_mental_health_index')]
    public function mentalHealth(EntityManagerInterface $em): Response
    {
        $evaluations = $em->getRepository(\App\Entity\EtatMental::class)
            ->findBy([], ['createdAt' => 'DESC']);

        return $this->render('coach/mental_health/index.html.twig', [
            'evaluations' => $evaluations,
        ]);
    }
}
