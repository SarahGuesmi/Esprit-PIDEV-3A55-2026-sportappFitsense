<?php

namespace App\Repository;

use App\Entity\ChatMessage;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChatMessage>
 */
class ChatMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatMessage::class);
    }

    /**
     * Messages between two users (conversation) visible to $userA (viewer), ordered by date.
     *
     * @return ChatMessage[]
     */
    public function findConversation(User $userA, User $userB, int $limit = 80): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.isDeleted = :isDeleted')
            ->setParameter('isDeleted', false)
            ->orderBy('c.createdAt', 'ASC')
            ->setMaxResults($limit);

        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->andX(
                    'c.sender = :userA',
                    'c.receiver = :userB',
                    'c.deletedBySenderAt IS NULL'
                ),
                $qb->expr()->andX(
                    'c.sender = :userB',
                    'c.receiver = :userA',
                    'c.deletedByReceiverAt IS NULL'
                )
            )
        );
        $qb->setParameter('userA', $userA);
        $qb->setParameter('userB', $userB);

        return $qb->getQuery()->getResult();
    }

    /**
     * New messages in conversation after lastId, visible to $userA (viewer).
     *
     * @return ChatMessage[]
     */
    public function findNewMessagesInConversation(User $userA, User $userB, int $lastId): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.id > :lastId')
            ->andWhere('c.isDeleted = :isDeleted')
            ->setParameter('lastId', $lastId)
            ->setParameter('isDeleted', false)
            ->orderBy('c.createdAt', 'ASC');

        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->andX(
                    'c.sender = :userA',
                    'c.receiver = :userB',
                    'c.deletedBySenderAt IS NULL'
                ),
                $qb->expr()->andX(
                    'c.sender = :userB',
                    'c.receiver = :userA',
                    'c.deletedByReceiverAt IS NULL'
                )
            )
        );
        $qb->setParameter('userA', $userA);
        $qb->setParameter('userB', $userB);

        return $qb->getQuery()->getResult();
    }

    /**
     * Total unread count for a user (messages where they are the receiver and readAt is null).
     */
    public function countUnreadForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.receiver = :user')
            ->andWhere('c.readAt IS NULL')
            ->andWhere('c.deletedByReceiverAt IS NULL')
            ->andWhere('c.isDeleted = :isDeleted')
            ->setParameter('user', $user)
            ->setParameter('isDeleted', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Unread count from a specific user (messages from $fromUser to $me that are unread).
     */
    public function countUnreadFromUser(User $me, User $fromUser): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.receiver = :me')
            ->andWhere('c.sender = :fromUser')
            ->andWhere('c.readAt IS NULL')
            ->andWhere('c.deletedByReceiverAt IS NULL')
            ->andWhere('c.isDeleted = :isDeleted')
            ->setParameter('me', $me)
            ->setParameter('fromUser', $fromUser)
            ->setParameter('isDeleted', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Mark all messages in conversation (from $other to $me) as read.
     */
    public function markConversationAsRead(User $me, User $other): void
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->update(ChatMessage::class, 'c')
            ->set('c.readAt', ':now')
            ->where('c.receiver = :me')
            ->andWhere('c.sender = :other')
            ->andWhere('c.readAt IS NULL')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('me', $me)
            ->setParameter('other', $other);
        $qb->getQuery()->execute();
    }

    /**
     * Mark specific messages (by ids) as read when receiver is $me.
     */
    public function markAsReadByIds(User $me, array $ids): void
    {
        if (empty($ids)) {
            return;
        }
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->update(ChatMessage::class, 'c')
            ->set('c.readAt', ':now')
            ->where('c.receiver = :me')
            ->andWhere('c.id IN (:ids)')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('me', $me)
            ->setParameter('ids', $ids);
        $qb->getQuery()->execute();
    }

    /**
     * Whether the conversation is "deleted" (hidden) for $me with $other.
     */
    public function isConversationDeletedForUser(User $me, User $other): bool
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.isDeleted = :isDeleted')
            ->setParameter('isDeleted', false);
        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->andX(
                    'c.sender = :me',
                    'c.receiver = :other',
                    'c.deletedBySenderAt IS NOT NULL'
                ),
                $qb->expr()->andX(
                    'c.sender = :other',
                    'c.receiver = :me',
                    'c.deletedByReceiverAt IS NOT NULL'
                )
            )
        );
        $qb->setParameter('me', $me);
        $qb->setParameter('other', $other);
        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Mark conversation as deleted (hidden) for $me with $other.
     */
    public function markConversationDeletedForUser(User $me, User $other): void
    {
        $now = new \DateTimeImmutable();
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->update(ChatMessage::class, 'c')
            ->set('c.deletedBySenderAt', ':now')
            ->where('c.sender = :me')
            ->andWhere('c.receiver = :other')
            ->setParameter('now', $now)
            ->setParameter('me', $me)
            ->setParameter('other', $other);
        $qb->getQuery()->execute();

        $qb2 = $em->createQueryBuilder();
        $qb2->update(ChatMessage::class, 'c')
            ->set('c.deletedByReceiverAt', ':now')
            ->where('c.sender = :other')
            ->andWhere('c.receiver = :me')
            ->setParameter('now', $now)
            ->setParameter('me', $me)
            ->setParameter('other', $other);
        $qb2->getQuery()->execute();
    }

    /**
     * Clear conversation deleted state for $me with $other (e.g. when sending a new message).
     */
    public function clearConversationDeletedForUser(User $me, User $other): void
    {
        $qb = $this->createQueryBuilder('c');
        $qb->andWhere('c.isDeleted = :isDeleted')
            ->setParameter('isDeleted', false)
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->andX('c.sender = :me', 'c.receiver = :other'),
                    $qb->expr()->andX('c.sender = :other', 'c.receiver = :me')
                )
            )
            ->setParameter('me', $me)
            ->setParameter('other', $other);
        $messages = $qb->getQuery()->getResult();

        $em = $this->getEntityManager();
        foreach ($messages as $msg) {
            if ($msg->getSender() === $me) {
                $msg->setDeletedBySenderAt(null);
            }
            if ($msg->getReceiver() === $me) {
                $msg->setDeletedByReceiverAt(null);
            }
        }
        $em->flush();
    }
}
