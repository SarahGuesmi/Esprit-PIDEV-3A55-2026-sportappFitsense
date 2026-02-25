<?php

namespace App\Security;

use App\Entity\ProfilePhysique;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AppAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'auth_sign_in';

    private UrlGeneratorInterface $urlGenerator;
    private EntityManagerInterface $entityManager;
    private \App\Service\LoginSecurityService $loginSecurityService;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        EntityManagerInterface $entityManager,
        \App\Service\LoginSecurityService $loginSecurityService
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->entityManager = $entityManager;
        $this->loginSecurityService = $loginSecurityService;
    }

    public function authenticate(Request $request): Passport
    {
        $ipAddress = $request->getClientIp();
        if ($this->loginSecurityService->isIpBlocked($ipAddress)) {
            throw new CustomUserMessageAuthenticationException('too_many_attempts');
        }

        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');

        $request->getSession()->set('_security.last_username', $email);

        $errors = [];

        // 1. Check for empty email
        if (empty($email)) {
            $errors['email'] = 'Email is required.';
        }

        // 2. Check for empty password
        if (empty($password)) {
            $errors['password'] = 'Password is required.';
        }

        // 3. If email provided, check if user exists and is active
        if (!empty($email)) {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if (!$user) {
                $errors['email'] = 'Email address not found.';
            } elseif ($user->getAccountStatus() === 'inactive') {
                $adminEmail = $this->getAdminEmail();
                $request->getSession()->set('deactivated_admin_email', $adminEmail);
                throw new CustomUserMessageAuthenticationException('account_deactivated');
            }
        }

        // If any pre-auth errors (empty or not found), stop here
        if (!empty($errors)) {
            $errorString = [];
            foreach ($errors as $field => $msg) {
                $errorString[] = "$field:$msg";
            }
            throw new CustomUserMessageAuthenticationException(implode('|', $errorString));
        }

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        $user = $token->getUser();
        
        // redirection par rôle (admin first)
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
        }

        if (in_array('ROLE_COACH', $user->getRoles(), true)) {
            return new RedirectResponse($this->urlGenerator->generate('coach_dashboard'));
        }

        // Check if user has a profile
        $profile = $this->entityManager->getRepository(ProfilePhysique::class)->findOneBy(['user' => $user]);
        
        if (!$profile) {
            // User doesn't have a profile, redirect to profile setup
            return new RedirectResponse($this->urlGenerator->generate('profile_setup_height'));
        }

        // User has a profile, redirect to dashboard
        return new RedirectResponse($this->urlGenerator->generate('dashboard_user'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($exception->getMessage() === 'too_many_attempts') {
            $adminEmail = $this->getAdminEmail();
            $request->getSession()->set('blocked_admin_email', $adminEmail);
            return new RedirectResponse($this->urlGenerator->generate(self::LOGIN_ROUTE));
        }
        if ($exception->getMessage() === 'account_deactivated') {
            return new RedirectResponse($this->urlGenerator->generate('account_deactivated'));
        }
        if ($exception instanceof BadCredentialsException) {
            // Wrap wrong password error in delimited string
            $exception = new CustomUserMessageAuthenticationException('password:Incorrect password.');
        }

        return parent::onAuthenticationFailure($request, $exception);
    }

    private function getAdminEmail(): string
    {
        $admin = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        return $admin instanceof User ? (string) $admin->getEmail() : '';
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }

    private function addFlash(Request $request, string $type, string $message): void
    {
        $request->getSession()->getFlashBag()->add($type, $message);
    }
}
