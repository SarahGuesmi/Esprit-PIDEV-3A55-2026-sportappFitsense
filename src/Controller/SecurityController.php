<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile/security')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class SecurityController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GoogleAuthenticatorInterface $googleAuthenticator,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    /**
     * Redirect to the unified security page in ProfileController.
     */
    #[Route('', name: 'security_2fa_settings', methods: ['GET'])]
    public function settings(): Response
    {
        return $this->redirectToRoute('profile_security');
    }

    /**
     * Start 2FA setup: generate secret, show QR code, then user must verify with a code.
     * Secret is stored in session until verification; only then persisted.
     */
    #[Route('/2fa/enable', name: 'security_2fa_enable', methods: ['GET', 'POST'])]
    public function enable(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isGoogleAuthenticatorEnabled()) {
            $this->addFlash('info', 'Two-factor authentication is already enabled.');
            return $this->redirectToRoute('profile_security');
        }

        $session = $request->getSession();
        $secret = $session->get('_2fa_setup_secret');
        if ($secret === null || $secret === '') {
            $secret = $this->googleAuthenticator->generateSecret();
            $session->set('_2fa_setup_secret', $secret);
        }
        $user->setGoogleAuthenticatorSecret($secret);
        $qrContent = $this->googleAuthenticator->getQRContent($user);

        // Use external QR service instead of local Endroid library (avoids extra PHP version constraints)
        $encoded = rawurlencode($qrContent);
        $qrCodeDataUri = sprintf(
            'https://api.qrserver.com/v1/create-qr-code/?size=192x192&data=%s',
            $encoded
        );

        if ($request->isMethod('POST')) {
            $code = trim((string) $request->request->get('_code', ''));
            if ($code === '') {
                $this->addFlash('error', 'Please enter the 6-digit code from your app.');
                return $this->render('security/2fa_enable.html.twig', [
                    'qr_code_data_uri' => $qrCodeDataUri,
                    'secret' => $secret,
                    'base_template' => $this->getBaseTemplateForUser($user),
                ]);
            }
            if ($this->googleAuthenticator->checkCode($user, $code)) {
                $session->remove('_2fa_setup_secret');
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                $this->addFlash('success', 'Two-factor authentication has been enabled.');
                return $this->redirectToRoute('profile_security');
            }
            $user->setGoogleAuthenticatorSecret(null);
            $this->addFlash('error', 'Invalid code. Please try again.');
        } else {
            $user->setGoogleAuthenticatorSecret(null);
        }

        return $this->render('security/2fa_enable.html.twig', [
            'qr_code_data_uri' => $qrCodeDataUri,
            'secret' => $secret,
            'base_template' => $this->getBaseTemplateForUser($user),
        ]);
    }

    /**
     * Disable 2FA after password confirmation.
     */
    #[Route('/2fa/disable', name: 'security_2fa_disable', methods: ['POST'])]
    public function disable(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->isGoogleAuthenticatorEnabled()) {
            $this->addFlash('info', 'Two-factor authentication is not enabled.');
            return $this->redirectToRoute('profile_security');
        }

        $password = $request->request->get('_password', '');
        if ($password === '') {
            $this->addFlash('error', 'Please enter your password to disable 2FA.');
            return $this->redirectToRoute('profile_security');
        }

        if (!$this->isCsrfTokenValid('disable_2fa', (string) $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('profile_security');
        }

        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            $this->addFlash('error', 'Incorrect password.');
            return $this->redirectToRoute('profile_security');
        }

        $user->setGoogleAuthenticatorSecret(null);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Two-factor authentication has been disabled.');
        return $this->redirectToRoute('profile_security');
    }

    /**
     * Reset pending 2FA setup (cancel before verifying code).
     */
    #[Route('/2fa/cancel-setup', name: 'security_2fa_cancel_setup', methods: ['POST'])]
    public function cancelSetup(Request $request): Response
    {
        $request->getSession()->remove('_2fa_setup_secret');
        $this->addFlash('info', '2FA setup cancelled.');
        return $this->redirectToRoute('profile_security');
    }

    private function getBaseTemplateForUser(User $user): string
    {
        $roles = $user->getRoles();
        if (in_array('ROLE_ADMIN', $roles, true)) {
            return 'admin/base_admin.html.twig';
        }
        if (in_array('ROLE_COACH', $roles, true)) {
            return 'coach/base_coach.html.twig';
        }
        return 'base_user.html.twig';
    }
}
