<?php

namespace App\Repository;

use App\Entity\RecetteNutritionnelle;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RecetteNutritionnelleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecetteNutritionnelle::class);
    }

    // ✅ Recherche côté USER (avec filtres + objectifs)
    public function searchForAll(?string $q, ?int $kcal, ?int $proteins, array $objectifs = []): array
    {
        $qb = $this->createQueryBuilder('r')
            ->orderBy('r.id', 'DESC');

        // objectifs (stockés en JSON)
        if (!empty($objectifs)) {
            $orX = $qb->expr()->orX();
            foreach ($objectifs as $i => $code) {
                $orX->add("r.objectifs LIKE :obj_$i");
                $qb->setParameter("obj_$i", '%"' . $code . '"%');
            }
            $qb->andWhere($orX);
        }

        if ($q) {
            $qb->andWhere('LOWER(r.title) LIKE :q OR LOWER(r.description) LIKE :q')
               ->setParameter('q', '%'.mb_strtolower($q).'%');
        }

        if ($kcal !== null) {
            $qb->andWhere('r.kcal <= :kcal')->setParameter('kcal', $kcal);
        }

        if ($proteins !== null) {
            $qb->andWhere('r.proteins >= :proteins')->setParameter('proteins', $proteins);
        }

        return $qb->getQuery()->getResult();
    }

    // ✅ TOP RECIPES PAR FAVORIS (ManyToMany)
    public function topFavoritesForCoach(User $coach, int $limit = 5): array
    {
        return $this->createQueryBuilder('r')
            ->select('r.title AS title, COUNT(u.id) AS favorites')
            ->leftJoin('r.favoritedBy', 'u')
            ->andWhere('r.coach = :coach')
            ->setParameter('coach', $coach)
            ->groupBy('r.id')
            ->orderBy('favorites', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
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
}