<?php

namespace App\Controller\Front;
use App\Entity\DailyNutrition;
use App\Entity\RecetteConsommee;
use App\Repository\DailyNutritionRepository;
use App\Repository\ProfilePhysiqueRepository;
use App\Repository\RecetteNutritionnelleRepository;
use App\Repository\WorkoutRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[IsGranted('ROLE_USER')]
class DashboardUserController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard_user', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('user_dashboard');
    }

    #[Route('/user/dashboard', name: 'user_dashboard', methods: ['GET'])]
public function dashboard(
    DailyNutritionRepository $dailyRepo,
    ProfilePhysiqueRepository $profileRepo,
    WorkoutRepository $workoutRepository,
    EntityManagerInterface $em,
    ChartBuilderInterface $chartBuilder
): Response {
    /** @var \App\Entity\User $user */
    $user = $this->getUser();

    $daily = $this->getOrCreateToday($user, $dailyRepo, $profileRepo, $em);

    if (!$daily) {
        $this->addFlash('error', 'Unable to load nutrition data for today. Please try again.');
        // Fallback to empty data to avoid crash
        $daily = new DailyNutrition();
        $daily->setCaloriesGoal(2000);
        $daily->setWaterGoal(2500);
    }

    // ✅ 7 derniers jours
    $end = (new \DateTimeImmutable('today'))->setTime(23, 59, 59);
    $start = (new \DateTimeImmutable('today'))->modify('-6 days')->setTime(0, 0, 0);

    $rows = $dailyRepo->findForUserBetween($user, $start, $end);

    $map = [];
    foreach ($rows as $r) {
        $map[$r->getDayDate()->format('Y-m-d')] = $r;
    }

    $labels = [];
    $caloriesData = [];

    for ($i = 0; $i < 7; $i++) {
        $day = $start->modify("+$i days");
        $key = $day->format('Y-m-d');

        $labels[] = $day->format('D d/m');
        $dn = $map[$key] ?? null;
        $caloriesData[] = $dn ? $dn->getCalories() : 0;
    }

    // ✅ Chart calories
$weeklyChart = $chartBuilder->createChart(Chart::TYPE_BAR);
  $weeklyChart->setData([
    'labels' => $labels,
    'datasets' => [[
        'label' => 'Calories (kcal)',
        'data' => $caloriesData,
        'backgroundColor' => 'rgba(56, 189, 248, 0.7)',
        'borderColor' => 'rgba(56, 189, 248, 1)',
        'borderWidth' => 1,
        'borderRadius' => 8,
    ]]
]);

    $weeklyChart->setOptions([
        'responsive' => true,
        'maintainAspectRatio' => false,
        'scales' => [
            'y' => ['beginAtZero' => true],
        ],
    ]);

    $recommendedWorkouts = $workoutRepository->findByUserObjectifs($user);

    return $this->render('front/dashboarduser.html.twig', [
        'user' => $user,
        'daily' => $daily,
        'weeklyChart' => $weeklyChart,              // ✅ IMPORTANT
        'recommendedWorkouts' => array_slice($recommendedWorkouts, 0, 3),
    ]);
}

    #[Route('/user/water/add-500', name: 'user_water_add_500', methods: ['POST'])]
    public function addWater500(
        Request $request,
        DailyNutritionRepository $dailyRepo,
        ProfilePhysiqueRepository $profileRepo,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('water_add', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $daily = $this->getOrCreateToday($user, $dailyRepo, $profileRepo, $em);
        if (!$daily) {
             $this->addFlash('error', 'Could not track water. Please try again.');
             return $this->redirectToRoute('user_dashboard');
        }
        $daily->setWaterMl($daily->getWaterMl() + 500);
        if ($daily->getWaterMl() >= $daily->getWaterGoal()) {
        $n = new \App\Entity\Notification();
        $n->setRelatedUser($user);
        $n->setType('water_goal_reached');
        $n->setMessage("🎉 Great job! You’ve reached your daily water goal: {$daily->getWaterMl()} / {$daily->getWaterGoal()} ml 💧");

    $em->persist($n);
}
$em->flush();

        $em->flush();
        return $this->redirectToRoute('user_dashboard');
    }

    #[Route('/user/recipe/{id}/consume', name: 'user_recipe_consume', methods: ['POST'])]
    public function consumeRecipe(
        int $id,
        Request $request,
        RecetteNutritionnelleRepository $recipeRepo,
        DailyNutritionRepository $dailyRepo,
        ProfilePhysiqueRepository $profileRepo,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('consume_meal', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $recipe = $recipeRepo->find($id);
        if (!$recipe) {
            $this->addFlash('error', 'Recipe not found.');
            return $this->redirectToRoute('user_dashboard');
        }

        $portion = (float) ($request->request->get('portion') ?? 1);

        // ✅ save consommation
        $cons = new RecetteConsommee();
        $cons->setUser($user);
        $cons->setRecette($recipe);
        $cons->setDateConsommation(new \DateTimeImmutable());
        $cons->setKcal((int) round(($recipe->getKcal() ?? 0) * $portion));

        $em->persist($cons);
        $em->flush();

        $daily = $this->getOrCreateToday($user, $dailyRepo, $profileRepo, $em);
        if (!$daily) {
             $this->addFlash('error', 'Meal recorded in history but failed to update daily total.');
             return $this->redirectToRoute('user_dashboard');
        }
        $daily->setCalories($daily->getCalories() + $cons->getKcal());
        $em->flush();

        $this->addFlash('success', 'Meal tracked successfully ✅');
        return $this->redirectToRoute('user_dashboard');
    }

    // ===========================
    //  API PREVIEW (no save)
    // ===========================


#[Route('/user/food/preview', name: 'user_food_preview', methods: ['POST'])]
public function previewFood(Request $request, HttpClientInterface $httpClient): JsonResponse
{
    $token = (string) $request->request->get('_token', '');
    if (!$this->isCsrfTokenValid('food_preview', $token)) {
        return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
    }

    $food = trim((string) $request->request->get('food', ''));
    if ($food === '') {
        return new JsonResponse(['success' => false, 'message' => 'Veuillez entrer un aliment'], 400);
    }

    $apiKey = $_ENV['USDA_API_KEY'] ?? '';
    if ($apiKey === '') {
        return new JsonResponse(['success' => false, 'message' => 'USDA_API_KEY manquante dans .env'], 500);
    }

    try {
        // 1) Search food
        $searchRes = $httpClient->request('GET', 'https://api.nal.usda.gov/fdc/v1/foods/search', [
            'query' => [
                'api_key' => $apiKey,
                'query'   => $food,
                'pageSize'=> 1,
            ],
            'timeout' => 20,
        ]);

        $searchData = $searchRes->toArray(false);
        $items = $searchData['foods'] ?? [];

        if (empty($items)) {
            return new JsonResponse(['success' => false, 'message' => 'Food not found'], 404);
        }

        $first = $items[0];
        $fdcId = $first['fdcId'] ?? null;
        $label = $first['description'] ?? $food;

        if (!$fdcId) {
            return new JsonResponse(['success' => false, 'message' => 'No FDC ID found'], 404);
        }

        // 2) Get details (nutrients)
        $detailRes = $httpClient->request('GET', "https://api.nal.usda.gov/fdc/v1/food/{$fdcId}", [
            'query' => ['api_key' => $apiKey],
            'timeout' => 20,
        ]);

        $detailData = $detailRes->toArray(false);

        // 3) Extract calories (Energy)
        $kcal = null;
        foreach (($detailData['foodNutrients'] ?? []) as $n) {
            $name = $n['nutrient']['name'] ?? '';
            $unit = $n['nutrient']['unitName'] ?? '';
            $val  = $n['amount'] ?? null;

            // Souvent: "Energy" en "KCAL"
            if (strtolower($name) === 'energy' && strtoupper($unit) === 'KCAL' && is_numeric($val)) {
                $kcal = (int) round((float) $val);
                break;
            }
        }

        if (!$kcal || $kcal <= 0) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Calories not available for this item',
            ], 404);
        }

        return new JsonResponse([
            'success' => true,
            'food'    => $label,
            'kcal'    => $kcal,
        ]);

    } catch (\Throwable $e) {
        return new JsonResponse([
            'success' => false,
            'message' => 'USDA API error',
            'error'   => $e->getMessage(),
        ], 500);
    }
}
    // ===========================
    //  ADD calories (save daily)
    //  ✅ NO API CALL HERE
    // ===========================
    #[Route('/user/food/add', name: 'user_food_add', methods: ['POST'])]
    public function addFood(
        Request $request,
        DailyNutritionRepository $dailyRepo,
        ProfilePhysiqueRepository $profileRepo,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('food_add', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $food = trim((string) $request->request->get('food'));
        $kcal = (int) $request->request->get('kcal');

        if ($food === '' || $kcal <= 0) {
            $this->addFlash('error', 'Please check calories first.');
            return $this->redirectToRoute('user_dashboard');
        }

        $daily = $this->getOrCreateToday($user, $dailyRepo, $profileRepo, $em);
        if (!$daily) {
             $this->addFlash('error', 'Failed to update daily total.');
             return $this->redirectToRoute('user_dashboard');
        }
        $daily->setCalories($daily->getCalories() + $kcal);
        if ($daily->getCalories() > $daily->getCaloriesGoal()) {
        $n = new \App\Entity\Notification();
        $n->setRelatedUser($user);
        $n->setType('calories_exceeded');
        $n->setMessage("⚠️ You exceeded your calorie goal: {$daily->getCalories()} / {$daily->getCaloriesGoal()} kcal");

    $em->persist($n);
}
$em->flush();
        $em->flush();

        $this->addFlash('success', "$food added: $kcal kcal ✅");
        return $this->redirectToRoute('user_dashboard');
    }

    // =======================
    // Helpers
    // =======================
  private function getOrCreateToday(
    $user,
    DailyNutritionRepository $dailyRepo,
    ProfilePhysiqueRepository $profileRepo,
    EntityManagerInterface $em
): ?DailyNutrition {

    $profile = $profileRepo->findOneBy(['user' => $user], ['id' => 'DESC']);
    $weight = $profile?->getWeight();

    $objectiveCodes = $this->getUserObjectiveCodes($user);

    $waterGoal = ($weight && $weight > 0) ? (int) round($weight * 30) : 2300;
    $caloriesGoal = $this->computeCaloriesGoalMulti($weight ? (float)$weight : null, $objectiveCodes);

    $daily = $dailyRepo->findTodayForUser($user);

    // ✅ si daily existe -> update goals si différent
    if ($daily) {
        if ($daily->getWaterGoal() !== $waterGoal) {
            $daily->setWaterGoal($waterGoal);
        }
        if ($daily->getCaloriesGoal() !== $caloriesGoal) {
            $daily->setCaloriesGoal($caloriesGoal);
        }
        $em->flush();
        return $daily;
    }

    // ✅ sinon create
    $daily = new DailyNutrition();
    $daily->setUser($user);
    $daily->setDayDate((new \DateTimeImmutable('today'))->setTime(0,0,0));
    $daily->setCalories(0);
    $daily->setWaterMl(0);
    $daily->setCaloriesGoal($caloriesGoal);
    $daily->setWaterGoal($waterGoal);

    try {
        $em->persist($daily);
        $em->flush();
    } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
        // En cas de conflit, on essaie de récupérer le record une dernière fois
        return $dailyRepo->findOneBy([
            'user' => $user,
            'dayDate' => new \DateTimeImmutable('today')
        ]);
    }

    return $daily;
}
    private function computeCaloriesGoalMulti(?float $weight, array $objectiveCodes): int
{
    if (!$weight || $weight <= 0) return 2000; // fallback

    // ✅ mapping objectif -> kcal/kg
    $map = [
        'WEIGHT_LOSS' => 24,
        'MUSCLE_GAIN' => 32,
        'ENDURANCE'   => 30,
        'WELL_BEING'  => 28,
    ];

    // si aucun objectif -> maintien
    if (empty($objectiveCodes)) {
        return (int) round($weight * 28);
    }

    $factors = [];
    foreach ($objectiveCodes as $code) {
        $code = strtoupper(trim((string)$code));
        if (isset($map[$code])) $factors[] = $map[$code];
    }

    // si codes inconnus -> maintien
    if (empty($factors)) {
        return (int) round($weight * 28);
    }

    $avg = array_sum($factors) / count($factors);

    return (int) round($weight * $avg);
}


private function getUserObjectiveCodes(\App\Entity\User $user): array
{
    $codes = [];

    // getObjectifs() peut être Collection (iterable)
    if (method_exists($user, 'getObjectifs')) {
        $objs = $user->getObjectifs();

        if (is_iterable($objs)) {
            foreach ($objs as $obj) {
                if (is_string($obj)) {
                    $codes[] = $obj;
                } elseif (is_object($obj)) {
                    if (method_exists($obj, 'getCode')) $codes[] = $obj->getCode();
                    elseif (method_exists($obj, 'getName')) $codes[] = $obj->getName();
                }
            }
        }
    }

    // fallback autre méthode
    if (empty($codes) && method_exists($user, 'getObjectifsSportifs')) {
        foreach ($user->getObjectifsSportifs() as $obj) {
            if (method_exists($obj, 'getCode')) $codes[] = $obj->getCode();
            elseif (method_exists($obj, 'getName')) $codes[] = $obj->getName();
        }
    }

    // mapping Name -> Code (si tu stockes des noms)
    $map = [
        'Weight Loss' => 'WEIGHT_LOSS',
        'Muscle Gain' => 'MUSCLE_GAIN',
        'Endurance'   => 'ENDURANCE',
        'Well-being'  => 'WELL_BEING',
    ];

    $final = [];
    foreach ($codes as $c) {
        $c = trim((string)$c);
        $final[] = $map[$c] ?? strtoupper($c);
    }

    return array_values(array_unique($final));
}
}