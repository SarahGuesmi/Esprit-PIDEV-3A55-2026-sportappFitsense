<?php

namespace App\Controller\Front;

use App\Entity\User;
use App\Service\OpenAiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/username')]
class UsernameSuggestionController extends AbstractController
{
    #[Route('/setup', name: 'username_setup', methods: ['GET'])]
    public function setup(Request $request, EntityManagerInterface $em): Response
    {
        $session = $request->getSession();
        $onboardingId = $session->get('onboarding_user_id');
        $user = null;

        // Priority to onboarding user from session
        if ($onboardingId) {
            $user = $em->getRepository(User::class)->find($onboardingId);
        }

        // Fallback to logged in user if no onboarding session
        if (!$user) {
            $user = $this->getUser();
        }

        // If still no user, redirect to sign-up
        if (!$user) {
            return $this->redirectToRoute('auth_sign_up');
        }

        // If username is already set, redirect back to dashboard
        if ($user && $user->getUsername()) {
            return $this->redirectToRoute('dashboard_user');
        }

        return $this->render('auth/username_setup.html.twig');
    }

    #[Route('/suggest', name: 'username_suggest', methods: ['GET'])]
    public function suggest(Request $request, OpenAiService $openAiService, EntityManagerInterface $em): JsonResponse
    {
        $onboardingId = $request->getSession()->get('onboarding_user_id');
        $user = null;

        if ($onboardingId) {
            $user = $em->getRepository(User::class)->find($onboardingId);
        }

        if (!$user) {
            $user = $this->getUser();
        }

        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        $suggestions = $openAiService->generateUsernameSuggestions(
            $user->getFirstname(),
            $user->getLastname()
        );

        return new JsonResponse(['usernames' => $suggestions]);
    }

    #[Route('/set', name: 'username_set', methods: ['POST'])]
    public function set(
        Request $request, 
        EntityManagerInterface $em,
        \Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface $userAuthenticator,
        \App\Security\AppAuthenticator $authenticator
    ): JsonResponse
    {
        $session = $request->getSession();
        $onboardingId = $session->get('onboarding_user_id');
        $user = null;

        if ($onboardingId) {
            $user = $em->getRepository(User::class)->find($onboardingId);
        }

        if (!$user) {
            $user = $this->getUser();
        }

        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $username = trim($data['username'] ?? '');

        if (empty($username)) {
            return new JsonResponse(['error' => 'Username cannot be empty'], 400);
        }

        // Check if username is already taken
        $existingUser = $em->getRepository(User::class)->findOneBy(['username' => $username]);
        if ($existingUser && $existingUser->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'This username is already taken'], 400);
        }

        $user->setUsername($username);
        $em->flush();

        // Remove onboarding session
        $session->remove('onboarding_user_id');

        // Manually authenticate the user if not already logged in
        if (!$this->getUser()) {
            $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return new JsonResponse(['success' => true]);
    }
}
