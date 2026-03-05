<?php

namespace App\Controller\Front;

use App\Service\DailyNutritionService;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Entity\RecetteConsommee;
use App\Repository\RecetteNutritionnelleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
#[IsGranted('ROLE_USER')]
class RecetteUserController extends AbstractController
{
    #[Route('/user/nutrition', name: 'user_nutrition')]
    public function index(
        Request $request,
        RecetteNutritionnelleRepository $repo
    ): Response {
        // Filters
        $q = trim((string) $request->query->get('q', ''));
        $kcal = $request->query->get('kcal');
        $proteins = $request->query->get('proteins');

        $kcal = ($kcal !== null && $kcal !== '') ? (int) $kcal : null;
        $proteins = ($proteins !== null && $proteins !== '') ? (int) $proteins : null;

        // Personalised Filtering by User Objectives
        $user = $this->getUser();
        $userCodes = [];
        if ($user instanceof \App\Entity\User) {
            $mapping = [
                'Weight Loss' => 'WEIGHT_LOSS',
                'Muscle Gain' => 'MUSCLE_GAIN',
                'Endurance'   => 'ENDURANCE',
                'Well-being'  => 'WELL_BEING',
            ];
            foreach ($user->getObjectifs() as $obj) {
                $name = $obj->getName();
                if (isset($mapping[$name])) {
                    $userCodes[] = $mapping[$name];
                }
            }
        }

        // Search with personalised filters
        $recipes = $repo->searchForAll($q ?: null, $kcal, $proteins, $userCodes);

        return $this->render('front/recette/index.html.twig', [
            'recipes' => $recipes,
            'q' => $q,
            'kcal' => $kcal,
            'proteins' => $proteins,
            'userObjectifs' => $userCodes,
        ]);
    }

    #[Route('/user/recipes/consume', name: 'user_recipe_consume', methods: ['POST'])]
    public function consumeMeal(
        Request $request,
        EntityManagerInterface $em,
        RecetteNutritionnelleRepository $repo,
        DailyNutritionService $nutritionService
    ): Response {
    /** @var \App\Entity\User $user */
    $user = $this->getUser();
    if (!$user) {
        throw $this->createAccessDeniedException();
    }

    $recipeId = (int) $request->request->get('recipeId');
    $portion  = (float) ($request->request->get('portion') ?? 1);

    $recette = $repo->find($recipeId);
    if (!$recette) {
        $this->addFlash('error', 'Recipe not found.');
        return $this->redirectToRoute('user_nutrition');
    }

    $cons = new RecetteConsommee();
    $cons->setUser($user);
    $cons->setRecette($recette);
    $cons->setDateConsommation(new \DateTimeImmutable());

    // ✅ calories & proteins: recette * portion
    $cons->setKcal((int) round(($recette->getKcal() ?? 0) * $portion));
    $cons->setProteins((int) round(($recette->getProteins() ?? 0) * $portion));

    // ✅ image optionnelle
    $file = $request->files->get('consumptionImage');
    if ($file) {
        $newName = uniqid('consumption_', true) . '.' . ($file->guessExtension() ?: 'jpg');
        $file->move($this->getParameter('consumption_upload_dir'), $newName);
        $cons->setImage($newName);
    }

    $em->persist($cons);
    $em->flush();

    // ✅ Sync with DailyNutrition for the chart
    $daily = $nutritionService->getOrCreateDaily($user);
    if ($daily) {
        $daily->setCalories($daily->getCalories() + $cons->getKcal());
        $em->flush();
    }

    $this->addFlash('success', 'Saved ✅');
    return $this->redirectToRoute('user_nutrition');
}

    #[Route('/user/food/preview', name: 'user_food_preview', methods: ['POST'])]
    public function foodPreview(Request $request, HttpClientInterface $http): JsonResponse
    {
        $food = trim((string) $request->request->get('food', ''));
        
        if ($food === '') {
            return $this->json(['success' => false, 'message' => 'Food name is required']);
        }

        try {
            $usdaApiKey = $_ENV['USDA_API_KEY'] ?? '';
            
            if (empty($usdaApiKey)) {
                return $this->json([
                    'success' => false, 
                    'message' => 'USDA API key not configured'
                ]);
            }

            // Call USDA FoodData Central API
            $response = $http->request('GET', 'https://api.nal.usda.gov/fdc/v1/foods/search', [
                'query' => [
                    'query' => $food,
                    'api_key' => $usdaApiKey,
                    'pageSize' => 1,
                ],
            ]);

            $data = $response->toArray();
            
            if (empty($data['foods'])) {
                return $this->json(['success' => false, 'message' => 'Food not found']);
            }

            $foodItem = $data['foods'][0];
            $nutrients = $foodItem['foodNutrients'] ?? [];
            
            // Find energy (calories) - nutrient ID 1008
            $kcal = 0;
            foreach ($nutrients as $nutrient) {
                if (($nutrient['nutrientId'] ?? 0) == 1008 || 
                    stripos($nutrient['nutrientName'] ?? '', 'Energy') !== false) {
                    $kcal = round($nutrient['value'] ?? 0);
                    break;
                }
            }
            
            return $this->json([
                'success' => true,
                'food' => $foodItem['description'] ?? $food,
                'kcal' => $kcal,
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'API error: ' . $e->getMessage()]);
        }
    }

    #[Route('/user/food/add', name: 'user_food_add', methods: ['POST'])]
    public function addFood(
        Request $request,
        EntityManagerInterface $em,
        DailyNutritionService $nutritionService
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $food = trim((string) $request->request->get('food', ''));
        $kcal = (int) $request->request->get('kcal', 0);

        if ($food === '' || $kcal <= 0) {
            $this->addFlash('error', 'Invalid food data');
            return $this->redirectToRoute('user_nutrition');
        }

        // Add to daily nutrition
        $daily = $nutritionService->getOrCreateDaily($user);
        if ($daily) {
            $daily->setCalories($daily->getCalories() + $kcal);
            $em->flush();
        }

        $this->addFlash('success', "Added {$food} ({$kcal} kcal) ✅");
        return $this->redirectToRoute('user_nutrition');
    }

#[Route('/user/recipes/themealdb-search', name: 'user_recipes_themealdb_search', methods: ['GET'])]
public function themealdbSearch(Request $request, HttpClientInterface $http){
    $q = trim((string) $request->query->get('q', ''));
    if ($q === '') {
        return $this->json(['success' => false, 'recipes' => []]);
    }

    // --- petit mapping FR -> EN (tu peux ajouter ce que tu veux)
    $map = [
        'oeuf' => 'egg', 'oeufs' => 'egg',
        'tomate' => 'tomato', 'tomates' => 'tomato',
        'epinard' => 'spinach', 'épinard' => 'spinach', 'epinards' => 'spinach', 'épinards' => 'spinach',
        'poulet' => 'chicken', 'viande' => 'beef',
        'fromage' => 'cheese', 'lait' => 'milk',
        'riz' => 'rice', 'pomme' => 'apple',
    ];

    // l’utilisateur peut écrire: "oeuf tomate epinard"
    $parts = preg_split('/[\s,;]+/', mb_strtolower($q));
    $parts = array_values(array_filter($parts, fn($x) => $x !== ''));

    // Convertir en anglais quand possible
    $ingredients = array_map(fn($p) => $map[$p] ?? $p, array_slice($parts, 0, 3)); // max 3 mots
    $ingredients = array_unique($ingredients);

    // --- 1) filter.php?i=ingredient (gratuit: 1 ingrédient)
    // on fait intersection des IDs
    $idSets = [];
    foreach ($ingredients as $ing) {
        $res = $http->request('GET', 'https://www.themealdb.com/api/json/v1/1/filter.php', [
            'query' => ['i' => $ing],
        ])->toArray();

        $meals = $res['meals'] ?? [];
        $ids = array_map(fn($m) => $m['idMeal'], $meals);
        $idSets[] = $ids;
    }

    if (empty($idSets)) {
        return $this->json(['success' => false, 'recipes' => []]);
    }

    $commonIds = $idSets[0];
    for ($i = 1; $i < count($idSets); $i++) {
        $commonIds = array_values(array_intersect($commonIds, $idSets[$i]));
    }

    // Si aucune recette commune, on fallback sur le 1er ingrédient (pour avoir quand même des résultats)
    if (count($commonIds) === 0) {
        $commonIds = $idSets[0];
    }

    $commonIds = array_slice($commonIds, 0, 4); // ✅ max 4

    // --- 2) lookup.php?i=ID (donne instructions + ingrédients)
    $recipes = [];
    foreach ($commonIds as $id) {
        $detail = $http->request('GET', 'https://www.themealdb.com/api/json/v1/1/lookup.php', [
            'query' => ['i' => $id],
        ])->toArray();

        $meal = ($detail['meals'][0] ?? null);
        if (!$meal) continue;

        // extraire ingrédients (strIngredient1..20)
        $ings = [];
        for ($k = 1; $k <= 20; $k++) {
            $name = trim((string)($meal["strIngredient$k"] ?? ''));
            $meas = trim((string)($meal["strMeasure$k"] ?? ''));
            if ($name !== '') {
                $ings[] = trim($meas . ' ' . $name);
            }
        }

        $recipes[] = [
            'id' => $meal['idMeal'],
            'title' => $meal['strMeal'] ?? '',
            'image' => $meal['strMealThumb'] ?? null,
            'category' => $meal['strCategory'] ?? null,
            'area' => $meal['strArea'] ?? null,
            'instructions' => $meal['strInstructions'] ?? '',
            'ingredients' => $ings,
        ];
    }

    return $this->json(['success' => true, 'recipes' => $recipes]);
}
}