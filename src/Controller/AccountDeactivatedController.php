<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AccountDeactivatedController extends AbstractController
{
    /**
     * Shown when a user with inactive account tries to sign in (email/password or Face ID).
     */
    #[Route('/account-deactivated', name: 'account_deactivated', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $adminEmail = (string) $request->getSession()->get('deactivated_admin_email', '');
        $request->getSession()->remove('deactivated_admin_email');

        return $this->render('auth/account_deactivated.html.twig', [
            'admin_email' => $adminEmail,
        ]);
    }
}
