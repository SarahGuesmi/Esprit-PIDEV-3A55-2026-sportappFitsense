<?php

namespace App\Controller\Coach;

use App\Entity\EtatMental;
use App\Entity\User;
use App\Service\OpenAiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/coach/ai')]
#[IsGranted('ROLE_COACH')]
class AiRecommendationController extends AbstractController
{
    #[Route('/recommend', name: 'coach_ai_recommend', methods: ['POST'])]
    public function recommend(Request $request, EntityManagerInterface $em, OpenAiService $openAiService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userId = $data['user_id'] ?? null;

        if (!$userId) {
            return new JsonResponse(['error' => 'Missing user ID'], 400);
        }

        $user = $em->getRepository(User::class)->find($userId);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        // Get the latest mental health assessment for this user
        $etatMental = $em->getRepository(EtatMental::class)->findOneBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        if (!$etatMental) {
            return new JsonResponse(['error' => 'No mental health assessment found for this user'], 404);
        }

        $recommendations = $openAiService->generateRecommendations($etatMental);
        return new JsonResponse(['recommendations' => $recommendations]);
    }
}
