<?php

namespace App\Twig;

use App\Repository\NotificationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NotificationExtension extends AbstractExtension
{
    private NotificationRepository $notificationRepository;
    private Security $security;

    public function __construct(NotificationRepository $notificationRepository, Security $security)
    {
        $this->notificationRepository = $notificationRepository;
        $this->security = $security;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('unread_notifications_count', [$this, 'getUnreadCount']),
            new TwigFunction('unread_user_nutrition_unread_count', [$this, 'getUnreadUserNutritionCount']),
        ];
    }

    public function getUnreadCount(): int
    {
        $user = $this->security->getUser();
        if (!$user) return 0;

        return $this->notificationRepository->count([
            'relatedUser' => $user,
            'isRead' => false
        ]);
    }

    public function getUnreadUserNutritionCount(): int
    {
        $user = $this->security->getUser();
        if (!$user) return 0;

        return $this->notificationRepository->countUnreadUserNutritionNotifications($user);
    }
}
