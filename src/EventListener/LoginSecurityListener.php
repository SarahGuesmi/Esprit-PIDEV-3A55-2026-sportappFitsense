<?php

namespace App\EventListener;

use App\Service\LoginSecurityService;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class LoginSecurityListener
{
    private LoginSecurityService $loginSecurityService;

    public function __construct(LoginSecurityService $loginSecurityService)
    {
        $this->loginSecurityService = $loginSecurityService;
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        $request = $event->getRequest();
        $ipAddress = $request->getClientIp();

        $this->loginSecurityService->recordAttempt($user->getUserIdentifier(), $ipAddress, 'success');
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $request = $event->getRequest();
        $ipAddress = $request->getClientIp();
        
        $email = $request->request->get('email', 'unknown');
        
        // Try to get email from passport if available
        $passport = $event->getPassport();
        if ($passport && $passport->hasBadge(UserBadge::class)) {
            $email = $passport->getBadge(UserBadge::class)->getUserIdentifier();
        }

        $this->loginSecurityService->recordAttempt($email, $ipAddress, 'failure');
    }
}
