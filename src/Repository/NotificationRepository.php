<?php

namespace App\Repository;

use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 *
 * @method Notification|null find($id, $lockMode = null, $lockVersion = null)
 * @method Notification|null findOneBy(array $criteria, array $orderBy = null)
 * @method Notification[]    findAll()
 * @method Notification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */

class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function countUnread(): int
    {
        return $this->count(['isRead' => false]);
    }
    public function findUserNutritionNotifications($user): array
{
    return $this->createQueryBuilder('n')
        ->andWhere('n.relatedUser = :u')
        ->andWhere('n.type IN (:types)')
        ->setParameter('u', $user)
        ->setParameter('types', ['calories_exceeded', 'water_goal_reached'])
        ->orderBy('n.createdAt', 'DESC')
        ->getQuery()
        ->getResult();
}

public function countUnreadUserNutritionNotifications($user): int
{
    return (int) $this->createQueryBuilder('n')
        ->select('COUNT(n.id)')
        ->andWhere('n.relatedUser = :u')
        ->andWhere('n.isRead = false')
        ->andWhere('n.type IN (:types)')
        ->setParameter('u', $user)
        ->setParameter('types', ['calories_exceeded', 'water_goal_reached'])
        ->getQuery()
        ->getSingleScalarResult();
}
}

