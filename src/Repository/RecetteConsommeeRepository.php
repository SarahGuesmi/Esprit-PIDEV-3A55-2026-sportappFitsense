<?php

namespace App\Repository;

use App\Entity\RecetteConsommee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RecetteConsommeeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecetteConsommee::class);
    }

    public function findAllOrderedByDate(int $limit = 100, int $offset = 0)
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findUserConsumptionStats(): array
    {
        // Fixed: Use NEW syntax with DTO for better performance and type safety
        // Aggregate consumption data by user
        return $this->createQueryBuilder('c')
            ->select('NEW App\DTO\UserConsumptionStatsDTO(
                IDENTITY(c.user),
                u.firstname,
                u.lastname,
                u.email,
                SUM(c.kcal),
                SUM(c.proteins),
                COUNT(c.id)
            )')
            ->join('c.user', 'u')
            ->groupBy('c.user')
            ->addGroupBy('u.firstname')
            ->addGroupBy('u.lastname')
            ->addGroupBy('u.email')
            ->orderBy('SUM(c.kcal)', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserOrderedByDate(int $userId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('c.dateConsommation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function sumForDay(int $userId, \DateTimeImmutable $day): array
    {
        $start = $day->setTime(0, 0, 0);
        $end   = $day->setTime(23, 59, 59);

        $res = $this->createQueryBuilder('c')
            ->select('
                COALESCE(SUM(c.kcal), 0) as totalKcal,
                COALESCE(SUM(c.proteins), 0) as totalProteins,
                COUNT(c.id) as mealCount
            ')
            ->andWhere('c.user = :userId')
            ->andWhere('c.dateConsommation BETWEEN :start AND :end')
            ->setParameter('userId', $userId)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleResult();

        return [
            'kcal' => (int) $res['totalKcal'],
            'proteins' => (float) $res['totalProteins'],
            'mealCount' => (int) $res['mealCount'],
        ];
    }

    public function sumKcalForUserOnDate(int $userId, \DateTimeInterface $date): int
{
    $start = (new \DateTimeImmutable($date->format('Y-m-d')))->setTime(0, 0, 0);
    $end   = $start->modify('+1 day');

    return (int) $this->createQueryBuilder('c')
        ->select('COALESCE(SUM(c.kcal), 0)')
        ->andWhere('c.user = :uid')
        ->andWhere('c.dateConsommation >= :start AND c.dateConsommation < :end')
        ->setParameter('uid', $userId)
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->getQuery()
        ->getSingleScalarResult();
}

public function sumProteinsForUserOnDate(int $userId, \DateTimeInterface $date): int
{
    $start = (new \DateTimeImmutable($date->format('Y-m-d')))->setTime(0, 0, 0);
    $end   = $start->modify('+1 day');

    return (int) $this->createQueryBuilder('c')
        ->select('COALESCE(SUM(c.proteins), 0)')
        ->andWhere('c.user = :uid')
        ->andWhere('c.dateConsommation >= :start AND c.dateConsommation < :end')
        ->setParameter('uid', $userId)
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->getQuery()
        ->getSingleScalarResult();
}
}
