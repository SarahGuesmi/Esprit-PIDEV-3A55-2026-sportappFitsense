<?php

namespace App\Controller\Front;

use App\Repository\RecetteNutritionnelleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class RecipeFavoriteController extends AbstractController
{
    #[IsGranted('ROLE_USER')]
    #[Route('/user/recipes/{id}/favorite', name: 'user_recipe_toggle_favorite', methods: ['POST'])]
    public function toggleFavorite(
        int $id,
        Request $request,
        RecetteNutritionnelleRepository $repo,
        EntityManagerInterface $em
    ): JsonResponse {
        $recipe = $repo->find($id);
        if (!$recipe) {
            return $this->json(['success' => false, 'message' => 'Recipe not found'], 404);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false], 401);
        }

        $isFav = $recipe->getFavoritedBy()->contains($user);

        if ($isFav) {
            $user->removeFavoriteRecipe($recipe);
            $favorited = false;
        } else {
            $user->addFavoriteRecipe($recipe);
            $favorited = true;
        }

        $em->flush();

        return $this->json(['success' => true, 'favorited' => $favorited]);
    }
}