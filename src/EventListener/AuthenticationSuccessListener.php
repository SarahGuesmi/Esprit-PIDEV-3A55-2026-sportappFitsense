<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class AuthenticationSuccessListener
{
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

 public function onLoginSuccess(LoginSuccessEvent $event): void
{
    $user = $event->getUser();
    if (!$user) {
        return;
    }

    if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
        $event->setResponse(
            new RedirectResponse($this->urlGenerator->generate('admin_dashboard'))
        );
    }
}


}
