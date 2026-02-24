<?php

namespace App\Controller\Front;

use App\Service\OpenAiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/chatbot')]
#[IsGranted('ROLE_USER')]
class ChatbotController extends AbstractController
{
    #[Route('/message', name: 'chatbot_message', methods: ['POST'])]
    public function message(Request $request, OpenAiService $openAiService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userMessage = trim($data['message'] ?? '');
        $history = $data['history'] ?? [];

        if (empty($userMessage)) {
            return new JsonResponse(['error' => 'Message cannot be empty'], 400);
        }

        $reply = $openAiService->chat($userMessage, $history, $this->getUser());

        return new JsonResponse(['reply' => $reply]);
    }
}
