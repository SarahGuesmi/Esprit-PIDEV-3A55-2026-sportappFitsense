<?php

namespace App\Controller\Front;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/profile/setup')]
class AvatarSetupController extends AbstractController
{
    /**
     * Show the avatar picker page with AI-generated seeds.
     */
    #[Route('/avatar', name: 'profile_setup_avatar', methods: ['GET'])]
    public function show(): Response
    {
        return $this->render('profile_setup/avatar_setup.html.twig', [
            'seeds' => $this->generateSeeds(),
        ]);
    }

    /**
     * AJAX endpoint to regenerate avatar seeds.
     */
    #[Route('/avatar/suggest', name: 'profile_setup_avatar_suggest', methods: ['GET'])]
    public function suggest(): JsonResponse
    {
        return new JsonResponse(['seeds' => $this->generateSeeds()]);
    }

    /**
     * Save the chosen avatar URL to the user's profile.
     */
    #[Route('/avatar/save', name: 'profile_setup_avatar_save', methods: ['POST'])]
    public function save(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $avatarUrl = trim($data['avatar_url'] ?? '');

        if (empty($avatarUrl)) {
            return new JsonResponse(['error' => 'No avatar selected'], 400);
        }

        // Store the DiceBear avatar URL in the photo field
        $user->setPhoto($avatarUrl);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * Generate 6 random unique seeds for Dicebear avatar variants.
     */
    private function generateSeeds(): array
    {
        $seeds = [];
        for ($i = 0; $i < 6; $i++) {
            $seeds[] = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 8) . $i;
        }
        return $seeds;
    }
}
