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
    // ... set roles, hash, persist, flush ...

    // On laisse le LISTENER décider de la redirection
    // (pas de set target_path ici → sinon il override le listener)

    return $userAuthenticator->authenticateUser(
        $user,
        $authenticator,
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
    // On ne gère plus le POST ici
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