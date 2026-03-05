<?php

namespace App\Repository;

use App\Entity\DailyNutrition;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DailyNutritionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DailyNutrition::class);
    }

    public function findTodayForUser($user): ?\App\Entity\DailyNutrition
    {
        $today = new \DateTimeImmutable('today');

        return $this->createQueryBuilder('d')
            ->andWhere('d.user = :u')
            ->andWhere('d.dayDate = :today')
            ->setParameter('u', $user)
            ->setParameter('today', $today)
            ->getQuery()
            ->getOneOrNullResult();
    }
 public function findForUserBetween(User $user, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.user = :u')
            ->andWhere('d.dayDate >= :start')
            ->andWhere('d.dayDate <= :end')
            ->setParameter('u', $user)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('d.dayDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}