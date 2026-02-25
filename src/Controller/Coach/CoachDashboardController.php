<?php

namespace App\Controller\Coach;

use App\Repository\RecetteNutritionnelleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class CoachDashboardController extends AbstractController
{
    #[Route('/coach/dashboard', name: 'coach_dashboard')]
    public function index(RecetteNutritionnelleRepository $repo, ChartBuilderInterface $chartBuilder): Response
    {
        $coach = $this->getUser();

        $top = $repo->topFavoritesForCoach($coach, 5); // title + favorites

        $labels = array_map(fn($x) => $x['title'], $top);
        $data   = array_map(fn($x) => (int)$x['favorites'], $top);

        // ✅ Build chart object for render_chart()
        $topRecipesChart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $topRecipesChart->setData([
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Favorites ❤️',
                'data' => $data,
                'borderWidth' => 1,
                'borderRadius' => 10,
            ]],
        ]);

        $topRecipesChart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => ['beginAtZero' => true],
            ],
        ]);

        return $this->render('coach/dashboard.html.twig', [
            'topRecipesChart' => $topRecipesChart,
            'hasFavoritesData' => count($labels) > 0 && count($data) > 0,
        ]);
    }
}