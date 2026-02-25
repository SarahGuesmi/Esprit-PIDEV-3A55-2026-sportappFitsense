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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\WorkoutRepository;
use App\Repository\ExerciseRepository;
use Symfony\UX\Chartjs\Model\Chart;
use Symfony\UX\Chartjs\Model\Dataset\PieDataset;
use Symfony\UX\Chartjs\Model\Dataset\BarDataset;
use Symfony\UX\Chartjs\Model\Dataset\DoughnutDataset;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;


#[Route('/coach')]
#[IsGranted('ROLE_COACH')]
class CoachController extends AbstractController
{
#[Route('/dashboard', name: 'coach_dashboard')]
public function dashboard(
    WorkoutRepository $workoutRepo,
    ExerciseRepository $exerciseRepo,
    ChartBuilderInterface $chartBuilder
): Response
{
    // ================= Total workouts =================
    $totalWorkouts = $workoutRepo->count([]);

    // ================= Durée moyenne =================
    $workouts = $workoutRepo->findAll();
    $totalDuration = array_sum(array_map(fn($w) => $w->getDuree() ?? 0, $workouts));
    $avgDuration = $workouts ? round($totalDuration / count($workouts), 2) : 0;

    // ================= Exercices les plus utilisés =================
    $exercises = $exerciseRepo->findAll();
    $exerciseUsage = [];
    foreach ($exercises as $ex) {
        $count = count($ex->getWorkouts());
        if ($count > 0) {
            $exerciseUsage[$ex->getNom()] = $count;
        }
    }
    arsort($exerciseUsage);
    $topExercise = array_key_first($exerciseUsage) ?? '—';

    // ================= Chart - Exercises Bar =================
    $exerciseChart = $chartBuilder->createChart(Chart::TYPE_BAR);
    $exerciseChart->setData([
        'labels' => array_keys($exerciseUsage),
        'datasets' => [[
            'label' => 'Uses',
            'data' => array_values($exerciseUsage),
            'backgroundColor' => [
                '#00f5a0','#38bdf8','#f472b6','#fbbf24','#a78bfa','#fb7185','#34d399','#818cf8'
            ],
        ]]
    ]);

    // ================= Chart - Goals Doughnut =================
    $goalDistribution = [];
    foreach ($workouts as $w) {
        $objectifs = $w->getObjectifs();
        if ($objectifs->isEmpty()) {
            $goalDistribution['Unknown'] = ($goalDistribution['Unknown'] ?? 0) + 1;
        } else {
            foreach ($objectifs as $obj) {
                $goalDistribution[$obj->getName()] = ($goalDistribution[$obj->getName()] ?? 0) + 1;
            }
        }
    }
    arsort($goalDistribution);

    $goalChart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
    $goalChart->setData([
        'labels' => array_keys($goalDistribution),
        'datasets' => [[
            'data' => array_values($goalDistribution),
            'backgroundColor' => [
                '#00f5a0','#38bdf8','#f472b6','#fbbf24','#a78bfa','#fb7185','#34d399','#818cf8'
            ],
        ]]
    ]);

    // ================= Chart - Levels Pie =================
    $levelDistribution = [];
    foreach ($workouts as $w) {
        $level = ucfirst(strtolower(trim($w->getNiveau() ?? 'Unknown')));
        $levelDistribution[$level] = ($levelDistribution[$level] ?? 0) + 1;
    }
    arsort($levelDistribution);

    $levelChart = $chartBuilder->createChart(Chart::TYPE_PIE);
    $levelChart->setData([
        'labels' => array_keys($levelDistribution),
        'datasets' => [[
            'data' => array_values($levelDistribution),
            'backgroundColor' => [
                '#00f5a0','#38bdf8','#f472b6','#fbbf24','#a78bfa','#fb7185','#34d399','#818cf8'
            ],
        ]]
    ]);

    return $this->render('coach/dashboard.html.twig', [
        'totalWorkouts' => $totalWorkouts,
        'avgDuration' => $avgDuration,
        'topExercise' => $topExercise,
        'exerciseChart' => $exerciseChart,
        'goalChart' => $goalChart,
        'levelChart' => $levelChart,
    ]);
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
    public function addRecommendation(Request $request, EntityManagerInterface $em): Response
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
