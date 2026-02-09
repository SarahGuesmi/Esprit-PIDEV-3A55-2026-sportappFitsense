<?php

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    #[Route('/sign-up', name: 'auth_sign_up')]
    public function signUp(): Response
    {
        return $this->render('auth/sign-up.html.twig');
    }

    #[Route('/sign-in', name: 'auth_sign_in')]
    public function signIn(): Response
    {
        return $this->render('auth/sign-in.html.twig');
    }
}