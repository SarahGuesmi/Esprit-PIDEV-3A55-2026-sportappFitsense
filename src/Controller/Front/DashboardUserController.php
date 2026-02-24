<?php

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use App\Entity\EtatMental;
use Doctrine\ORM\EntityManagerInterface;

#[IsGranted('ROLE_USER')]
class DashboardUserController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard_user')]
    public function index(
        \App\Repository\WorkoutRepository $workoutRepository, 
        EntityManagerInterface $em,
        ChartBuilderInterface $chartBuilder
    ): Response
    {
        $user = $this->getUser();
        $recommendedWorkouts = $workoutRepository->findByUserObjectifs($user);

        // Fetch wellness data for chart
        $evaluations = $em->getRepository(EtatMental::class)->findBy(
            ['user' => $user],
            ['createdAt' => 'ASC'], // ASC for the line trend
            7 // Last 7 entries
        );

        $labels = [];
        $data = [];
        foreach ($evaluations as $eval) {
            $labels[] = $eval->getCreatedAt()->format('d/m');
            $data[] = $eval->getTotalScore();
        }

        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Wellness Score',
                    'backgroundColor' => 'rgba(167, 139, 250, 0.2)',
                    'borderColor' => '#A78BFA',
                    'data' => $data,
                    'tension' => 0.4,
                    'pointRadius' => 4,
                    'fill' => true,
                ],
            ],
        ]);

        $chart->setOptions([
            'scales' => [
                'y' => [
                    'suggestedMin' => 0,
                    'suggestedMax' => 25,
                    'ticks' => ['color' => '#A1A1AA'],
                    'grid' => ['color' => 'rgba(255, 255, 255, 0.05)']
                ],
                'x' => [
                    'ticks' => ['color' => '#A1A1AA'],
                    'grid' => ['display' => false]
                ],
            ],
            'plugins' => [
                'legend' => ['display' => false]
            ],
            'maintainAspectRatio' => false,
        ]);

        return $this->render('front/dashboarduser.html.twig', [
            'user' => $user,
            'recommendedWorkouts' => array_slice($recommendedWorkouts, 0, 3),
            'chart' => $chart,
            'lastEvaluation' => end($evaluations) ?: null,
        ]);
    }
}
