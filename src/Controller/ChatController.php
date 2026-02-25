<?php

namespace App\Controller;

use App\Entity\ChatMessage;
use App\Entity\User;
use App\Repository\ChatMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/chat')]
class ChatController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ChatMessageRepository $chatMessageRepository
    ) {
    }

    /**
     * Chatroom: list of contacts (by role) + conversation with selected user.
     * Admin: coaches only. Coach: admins + athletes. Athlete: coaches only.
     */
    #[Route('', name: 'app_chat_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $me = $this->getUser();
        if (!$me instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $contacts = $this->getContactsForCurrentUser();
        $withId = $request->query->getInt('with', 0);
        $selectedUser = null;
        $messages = [];
        $unreadCounts = [];

        $conversationDeleted = false;
        if ($withId > 0) {
            $selectedUser = $this->entityManager->getRepository(User::class)->find($withId);
            if ($selectedUser && $this->isAllowedContact($selectedUser)) {
                if ($this->chatMessageRepository->isConversationDeletedForUser($me, $selectedUser)) {
                    $messages = [];
                    $conversationDeleted = true;
                } else {
                    $messages = $this->chatMessageRepository->findConversation($me, $selectedUser);
                    $this->chatMessageRepository->markConversationAsRead($me, $selectedUser);
                }
            } else {
                $selectedUser = null;
            }
        }

        foreach ($contacts as $contact) {
            $unreadCounts[$contact->getId()] = $this->chatMessageRepository->countUnreadFromUser($me, $contact);
        }

        $baseTemplate = $this->getBaseTemplate();

        return $this->render('chat/chatroom.html.twig', [
            'contacts' => $contacts,
            'selectedUser' => $selectedUser,
            'messages' => $messages,
            'unreadCounts' => $unreadCounts,
            'conversationDeleted' => $conversationDeleted,
            'base_template' => $baseTemplate,
        ]);
    }

    #[Route('/unread-count', name: 'app_chat_unread_count', methods: ['GET'])]
    public function unreadCount(): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $me = $this->getUser();
        if (!$me instanceof User) {
            return new JsonResponse(['total' => 0]);
        }
        $total = $this->chatMessageRepository->countUnreadForUser($me);
        return new JsonResponse(['total' => $total]);
    }

    #[Route('/send', name: 'app_chat_send', methods: ['POST'])]
    public function send(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $me = $this->getUser();
        if (!$me instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $content = trim((string) $request->request->get('content', ''));
        $receiverId = $request->request->getInt('receiver', 0);
        if (!$content || $receiverId <= 0) {
            return new JsonResponse(['error' => 'Content and receiver are required'], Response::HTTP_BAD_REQUEST);
        }

        $receiver = $this->entityManager->getRepository(User::class)->find($receiverId);
        if (!$receiver || !$this->isAllowedContact($receiver)) {
            return new JsonResponse(['error' => 'Invalid receiver'], Response::HTTP_FORBIDDEN);
        }

        $this->chatMessageRepository->clearConversationDeletedForUser($me, $receiver);

        $message = new ChatMessage();
        $message->setContent($content);
        $message->setSender($me);
        $message->setReceiver($receiver);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $message->getId(),
            'content' => $message->getContent(),
            'sender' => $message->getSender()->getFirstname() . ' ' . $message->getSender()->getLastname(),
            'senderId' => $message->getSender()->getId(),
            'createdAt' => $message->getCreatedAt()->format('H:i'),
        ]);
    }

    #[Route('/fetch-new', name: 'app_chat_fetch_new', methods: ['GET'])]
    public function fetchNew(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $me = $this->getUser();
        if (!$me instanceof User) {
            return new JsonResponse([], Response::HTTP_OK);
        }

        $withId = $request->query->getInt('with', 0);
        $lastId = $request->query->getInt('lastId', 0);
        if ($withId <= 0) {
            return new JsonResponse([]);
        }

        $other = $this->entityManager->getRepository(User::class)->find($withId);
        if (!$other || !$this->isAllowedContact($other)) {
            return new JsonResponse([]);
        }
        if ($this->chatMessageRepository->isConversationDeletedForUser($me, $other)) {
            return new JsonResponse([]);
        }

        $newMessages = $this->chatMessageRepository->findNewMessagesInConversation($me, $other, $lastId);
        $idsToMarkRead = [];
        $data = [];
        foreach ($newMessages as $msg) {
            if ($msg->getReceiver() === $me) {
                $idsToMarkRead[] = $msg->getId();
            }
            $data[] = [
                'id' => $msg->getId(),
                'content' => $msg->getContent(),
                'sender' => $msg->getSender()->getFirstname() . ' ' . $msg->getSender()->getLastname(),
                'senderId' => $msg->getSender()->getId(),
                'createdAt' => $msg->getCreatedAt()->format('H:i'),
            ];
        }
        if (!empty($idsToMarkRead)) {
            $this->chatMessageRepository->markAsReadByIds($me, $idsToMarkRead);
        }

        return new JsonResponse($data);
    }

    #[Route('/delete/{id}', name: 'app_chat_delete', methods: ['DELETE'])]
    public function delete(ChatMessage $message): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $me = $this->getUser();
        if (!$me instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        if ($message->getSender() !== $me) {
            return new JsonResponse(['error' => 'You can only delete your own messages'], Response::HTTP_FORBIDDEN);
        }

        $message->setIsDeleted(true);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/delete-conversation', name: 'app_chat_delete_conversation', methods: ['POST'])]
    public function deleteConversation(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $me = $this->getUser();
        if (!$me instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $otherId = $request->request->getInt('other', 0);
        if ($otherId <= 0) {
            return new JsonResponse(['error' => 'Invalid conversation'], Response::HTTP_BAD_REQUEST);
        }

        $other = $this->entityManager->getRepository(User::class)->find($otherId);
        if (!$other || !$this->isAllowedContact($other)) {
            return new JsonResponse(['error' => 'Invalid conversation'], Response::HTTP_FORBIDDEN);
        }

        $this->chatMessageRepository->markConversationDeletedForUser($me, $other);

        return new JsonResponse(['success' => true]);
    }

    private function getContactsForCurrentUser(): array
    {
        $me = $this->getUser();
        if (!$me instanceof User) {
            return [];
        }

        $repo = $this->entityManager->getRepository(User::class);
        $qb = $repo->createQueryBuilder('u')
            ->andWhere('u.id != :me')
            ->setParameter('me', $me->getId())
            ->andWhere('u.accountStatus = :status')
            ->setParameter('status', 'active')
            ->orderBy('u.firstname', 'ASC')
            ->addOrderBy('u.lastname', 'ASC');

        if ($this->isGranted('ROLE_ADMIN')) {
            $qb->andWhere('u.roles LIKE :role')->setParameter('role', '%ROLE_COACH%');
            return $qb->getQuery()->getResult();
        }

        if ($this->isGranted('ROLE_COACH')) {
            $users = $qb->getQuery()->getResult();
            $contacts = [];
            foreach ($users as $u) {
                $roles = $u->getRoles();
                if (in_array('ROLE_ADMIN', $roles, true)) {
                    $contacts[] = $u;
                } elseif (!in_array('ROLE_COACH', $roles, true)) {
                    $contacts[] = $u;
                }
            }
            usort($contacts, fn (User $a, User $b) => strcasecmp($a->getFirstname() . ' ' . $a->getLastname(), $b->getFirstname() . ' ' . $b->getLastname()));
            return $contacts;
        }

        $qb->andWhere('u.roles LIKE :role')->setParameter('role', '%ROLE_COACH%');
        return $qb->getQuery()->getResult();
    }

    private function isAllowedContact(User $other): bool
    {
        $me = $this->getUser();
        if (!$me instanceof User || $me->getId() === $other->getId()) {
            return false;
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            return in_array('ROLE_COACH', $other->getRoles(), true);
        }
        if ($this->isGranted('ROLE_COACH')) {
            $roles = $other->getRoles();
            return in_array('ROLE_ADMIN', $roles, true) || !in_array('ROLE_COACH', $roles, true);
        }
        return in_array('ROLE_COACH', $other->getRoles(), true);
    }

    private function getBaseTemplate(): string
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return 'admin/base_admin.html.twig';
        }
        if ($this->isGranted('ROLE_COACH')) {
            return 'coach/base_coach.html.twig';
        }
        return 'base_user.html.twig';
    }
}
