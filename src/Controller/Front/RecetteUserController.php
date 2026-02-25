<?php

namespace App\Controller\Front;

use App\Entity\RecetteConsommee;
use App\Repository\RecetteNutritionnelleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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

    #[Route('/user/recipe/{id}/consume', name: 'user_recipe_consume', methods: ['POST'])]
    public function consume(
        int $id,
        Request $request,
        RecetteNutritionnelleRepository $repo,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $recette = $repo->find($id);
        if (!$recette) {
            throw $this->createNotFoundException('Recipe not found');
        }

        $consommee = new RecetteConsommee();
        $consommee->setUser($user);
        $consommee->setRecette($recette);
        $consommee->setKcal($recette->getKcal() ?? 0);
        $consommee->setProteins($recette->getProteins() ?? 0);

        // Handle optional image upload
        $imageFile = $request->files->get('consumptionImage');
        if ($imageFile) {
            $newFilename = uniqid('consumption_') . '.' . ($imageFile->guessExtension() ?: 'jpg');
            try {
                $imageFile->move(
                    $this->getParameter('consumption_upload_dir'),
                    $newFilename
                );
                $consommee->setImage($newFilename);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error uploading image');
            }
        }

        $em->persist($consommee);
        $em->flush();

        $this->addFlash('success', 'Meal tracked successfully! 🥗💪');

        return $this->redirectToRoute('user_nutrition');
    }
}
