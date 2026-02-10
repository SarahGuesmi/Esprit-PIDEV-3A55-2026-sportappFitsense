<?php

namespace App\Controller\Front;

use App\Entity\User;
use App\Form\UserRegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\AppAuthenticator; 

class AuthController extends AbstractController
{
    // =========================
    // SIGN UP
    // =========================
    #[Route('/sign-up', name: 'auth_sign_up', methods: ['GET', 'POST'])]
    public function signUp(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        UserAuthenticatorInterface $userAuthenticator,
        AppAuthenticator $authenticator   // ← FIXED: changed from LoginFormAuthenticator
    ): Response {
        $user = new User();

        $form = $this->createForm(UserRegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Définition des champs invisibles
            $user->setRoles(['ROLE_USER']);
            $user->setAccountStatus('active');
            $user->setDateCreation(new \DateTimeImmutable());

            // Hash du mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush();

            // Set target path for redirection after auto-login
            $request->getSession()->set(
                '_security.main.target_path',
                $this->generateUrl('profile_setup_height')
            );

            // Auto-login the user
            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,          // ← now matches the variable name
                $request
            );
        }

        return $this->render('auth/sign-up.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // =========================
    // SIGN IN (LOGIN)
    // =========================
    #[Route('/sign-in', name: 'auth_sign_in', methods: ['GET', 'POST'])]
    public function signIn(AuthenticationUtils $authUtils): Response
    {
        // Si déjà connecté → redirection
        if ($this->getUser()) {
            return $this->redirectToRoute('dashboard');
        }

        return $this->render('auth/sign-in.html.twig', [
            'last_username' => $authUtils->getLastUsername(),
            'error' => $authUtils->getLastAuthenticationError(),
        ]);
    }

    // =========================
    // LOGOUT
    // =========================
    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method is intercepted by Symfony security.');
    }
}