<?php

namespace App\Controller\Front;

use App\Repository\WorkoutRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardUserController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard_user')]
    public function index(
        WorkoutRepository $workoutRepository
    ): Response {
        $user = $this->getUser();
        $recommendedWorkouts = $workoutRepository->findByUserObjectifs($user);

        return $this->render('front/dashboarduser.html.twig', [
            'user' => $user,
            'recommendedWorkouts' => array_slice($recommendedWorkouts, 0, 3),
        ]);
    }
}

