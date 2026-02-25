<?php

namespace App\Controller\Front;

use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class UserNotificationController extends AbstractController
{
    #[Route('/user/notifications', name: 'user_notifications')]
public function index(NotificationRepository $repo): Response
{
    /** @var \App\Entity\User $user */
    $user = $this->getUser();

    $notifications = $repo->findUserNutritionNotifications($user);
    $unreadCount   = $repo->countUnreadUserNutritionNotifications($user);

    return $this->render('front/notifications/index.html.twig', [
        'notifications' => $notifications,
        'unreadCount' => $unreadCount,
    ]);
}

    #[Route('/user/notifications/{id}/read', name: 'user_notifications_mark_read')]
    public function markRead(int $id, NotificationRepository $repo, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $n = $repo->find($id);
        if (!$n || $n->getRelatedUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $n->setIsRead(true);
        $em->flush();

        return $this->redirectToRoute('user_notifications');
    }
}